<?php
namespace App\Support;

use App\Services\OpenAIHandler;
use App\Services\OpenAIHandlerMock;
use App\Helpers\ModeHelper;

/**
 * Advanced categorization engine with AI integration
 * Supports priority-based rules with complex conditions
 */
class CategoryRules {
    
    /**
     * Categorize a message using rules and AI assistance
     */
    public static function categorize(?string $subject, string $message, ?string $aiReply = null): string {
        $rules = self::loadRulesFromFile();
        $context = self::buildContext($subject, $message, $aiReply);
        
        // First try rule-based categorization
        $ruleCategory = self::applyRules($rules, $context);
        if ($ruleCategory) {
            return $ruleCategory;
        }
        
        // If no rule matches and AI categorization is enabled, try AI
        $aiEnabled = Settings::get('ai_categorization_enabled', true);
        if ($aiEnabled) {
            $aiCategory = self::getAISuggestion($context);
            if ($aiCategory) {
                return $aiCategory;
            }
        }
        
        // Fallback to default
        return Settings::get('default_category', 'General');
    }
    
    /**
     * Get AI suggestion for category with confidence scoring
     */
    public static function getAISuggestion(array $context): ?string {
        try {
            $availableCategories = self::getAvailableCategories();
            $prompt = self::buildAIPrompt($context, $availableCategories);
            
            $aiService = ModeHelper::isMock() ? OpenAIHandlerMock::class : OpenAIHandler::class;
            $response = $aiService::query($prompt);
            
            return self::parseAIResponse($response, $availableCategories);
        } catch (\Throwable $e) {
            error_log('AI categorization error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Apply priority-based rules to context
     */
    protected static function applyRules(array $rules, array $context): ?string {
        // Sort by priority (higher priority first)
        usort($rules, function($a, $b) {
            return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
        });
        
        foreach ($rules as $rule) {
            if (!isset($rule['conditions']) || !isset($rule['category'])) {
                continue;
            }
            
            if (self::evaluateConditions($rule['conditions'], $context)) {
                return $rule['category'];
            }
        }
        
        return null;
    }
    
    /**
     * Evaluate rule conditions against context
     */
    protected static function evaluateConditions(array $conditions, array $context): bool {
        // Handle AND conditions
        if (isset($conditions['all'])) {
            foreach ($conditions['all'] as $condition) {
                if (!self::evaluateCondition($condition, $context)) {
                    return false;
                }
            }
            return true;
        }
        
        // Handle OR conditions  
        if (isset($conditions['any'])) {
            foreach ($conditions['any'] as $condition) {
                if (self::evaluateCondition($condition, $context)) {
                    return true;
                }
            }
            return false;
        }
        
        // Handle single condition
        return self::evaluateCondition($conditions, $context);
    }
    
    /**
     * Evaluate a single condition
     */
    protected static function evaluateCondition(array $condition, array $context): bool {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'contains';
        $value = $condition['value'] ?? '';
        $caseSensitive = $condition['case_sensitive'] ?? false;
        
        if (!isset($context[$field])) {
            return false;
        }
        
        $fieldValue = $context[$field];
        
        if (!$caseSensitive) {
            $fieldValue = mb_strtolower($fieldValue);
            $value = mb_strtolower($value);
        }
        
        switch ($operator) {
            case 'contains':
                return mb_strpos($fieldValue, $value) !== false;
            case 'starts_with':
                return mb_strpos($fieldValue, $value) === 0;
            case 'ends_with':
                return mb_substr($fieldValue, -mb_strlen($value)) === $value;
            case 'equals':
                return $fieldValue === $value;
            case 'not_equals':
                return $fieldValue !== $value;
            case 'regex':
                return preg_match('/' . $value . '/', $fieldValue) === 1;
            case 'length_gt':
                return mb_strlen($fieldValue) > (int)$value;
            case 'length_lt':
                return mb_strlen($fieldValue) < (int)$value;
            default:
                return false;
        }
    }
    
    /**
     * Build context array from input data
     */
    protected static function buildContext(?string $subject, string $message, ?string $aiReply): array {
        return [
            'subject' => $subject ?: '',
            'message' => $message,
            'ai_reply' => $aiReply ?: '',
            'combined' => trim(($subject ?: '') . ' ' . $message . ' ' . ($aiReply ?: '')),
            'message_length' => mb_strlen($message),
            'has_question_mark' => strpos($message, '?') !== false,
            'has_exclamation' => strpos($message, '!') !== false,
            'word_count' => str_word_count($message),
        ];
    }
    
    /**
     * Build AI prompt for categorization
     */
    protected static function buildAIPrompt(array $context, array $categories): string {
        $categoriesText = implode(', ', $categories);
        
        return "Analyze this support message and suggest the most appropriate category.\n\n" .
               "Available categories: {$categoriesText}\n\n" .
               "Message: \"{$context['message']}\"\n\n" .
               "Respond with just the category name that best fits this message. " .
               "If none fit well, respond with 'General'.";
    }
    
    /**
     * Parse AI response and validate against available categories
     */
    protected static function parseAIResponse(array $response, array $categories): ?string {
        $suggestion = trim($response['reply'] ?? '');
        
        // Check if suggestion matches available categories (case-insensitive)
        foreach ($categories as $category) {
            if (strcasecmp($suggestion, $category) === 0) {
                return $category;
            }
        }
        
        return null;
    }
    
    /**
     * Get list of available categories from rules
     */
    public static function getAvailableCategories(): array {
        $rules = self::loadRulesFromFile();
        $categories = ['General']; // Always include default
        
        foreach ($rules as $rule) {
            if (isset($rule['category']) && !in_array($rule['category'], $categories)) {
                $categories[] = $rule['category'];
            }
        }
        
        return $categories;
    }
    
    /**
     * Load rules from configuration
     */
    public static function loadRules(): array {
        return self::loadRulesFromFile();
    }
    
    /**
     * Load rules from configuration (internal)
     */
    protected static function loadRulesFromFile(): array {
        $path = __DIR__ . '/../../storage/config/category_rules.json';
        if (!is_file($path)) {
            return self::createDefaultRules();
        }
        
        $json = @file_get_contents($path);
        if ($json === false) {
            return self::createDefaultRules();
        }
        
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return self::createDefaultRules();
        }
        
        return $data;
    }
    
    /**
     * Save rules to configuration
     */
    public static function saveRules(array $rules): bool {
        $path = __DIR__ . '/../../storage/config/category_rules.json';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        
        return @file_put_contents($path, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }
    
    /**
     * Create default rules structure
     */
    protected static function createDefaultRules(): array {
        $defaults = [
            [
                'id' => 1,
                'name' => 'Billing and Refunds',
                'priority' => 100,
                'category' => 'Billing',
                'conditions' => [
                    'any' => [
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'refund'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'billing'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'invoice'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'payment'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'charged'],
                    ]
                ]
            ],
            [
                'id' => 2,
                'name' => 'Technical Support',
                'priority' => 90,
                'category' => 'Support',
                'conditions' => [
                    'any' => [
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'bug'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'error'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'not working'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'broken'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'issue'],
                    ]
                ]
            ],
            [
                'id' => 3,
                'name' => 'Sales Inquiries',
                'priority' => 80,
                'category' => 'Sales',
                'conditions' => [
                    'any' => [
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'buy'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'price'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'discount'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'upgrade'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'purchase'],
                    ]
                ]
            ],
            [
                'id' => 4,
                'name' => 'Feature Requests',
                'priority' => 70,
                'category' => 'Feature Request',
                'conditions' => [
                    'any' => [
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'feature'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'suggestion'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'enhancement'],
                        ['field' => 'message', 'operator' => 'contains', 'value' => 'improve'],
                    ]
                ]
            ]
        ];
        
        self::saveRules($defaults);
        return $defaults;
    }
    
    /**
     * Test categorization for debugging
     */
    public static function testCategorization(string $message, ?string $subject = null): array {
        $context = self::buildContext($subject, $message, null);
        $rules = self::loadRulesFromFile();
        
        $result = [
            'message' => $message,
            'context' => $context,
            'rule_match' => null,
            'ai_suggestion' => null,
            'final_category' => 'General'
        ];
        
        // Test rule matching
        foreach ($rules as $rule) {
            if (isset($rule['conditions']) && self::evaluateConditions($rule['conditions'], $context)) {
                $result['rule_match'] = $rule;
                $result['final_category'] = $rule['category'];
                break;
            }
        }
        
        // Test AI suggestion if no rule matched
        if (!$result['rule_match']) {
            $result['ai_suggestion'] = self::getAISuggestion($context);
            if ($result['ai_suggestion']) {
                $result['final_category'] = $result['ai_suggestion'];
            }
        }
        
        return $result;
    }
}
