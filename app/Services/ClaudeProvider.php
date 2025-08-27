<?php
namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\Core\Env;

/**
 * Claude (Anthropic) AI provider implementation
 */
class ClaudeProvider implements AIProviderInterface
{
    protected array $config;
    
    public function __construct()
    {
        $this->config = $this->getConfig();
    }
    
    public function query(string $prompt, array $options = []): array
    {
        $apiKey = $this->config['api_key'];
        $model = $options['model'] ?? $this->config['model'];
        $maxTokens = $options['max_tokens'] ?? $this->config['max_tokens'];
        $temperature = $options['temperature'] ?? $this->config['temperature'];
        
        if (!$apiKey || strtoupper($apiKey) === 'MOCK_MODE') {
            return [
                'reply' => 'Claude API key not configured',
                'category' => 'Support',
                'confidence' => 0.0,
                'tokens_used' => 0
            ];
        }
        
        $payload = json_encode([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        $ch = curl_init(rtrim($this->config['api_base'], '/') . '/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'anthropic-dangerous-direct-browser-access: true'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($raw === false) {
            $err = curl_error($ch);
            error_log('Claude cURL error: ' . $err);
            curl_close($ch);
            return ['reply' => 'AI error: ' . $err, 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => 0];
        }
        
        if ($code !== 200) {
            error_log('Claude HTTP ' . $code . ' response: ' . substr($raw, 0, 1000));
            curl_close($ch);
            return ['reply' => 'AI service unavailable (HTTP ' . $code . ').', 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => 0];
        }
        
        curl_close($ch);
        $data = json_decode($raw, true);
        
        $text = '';
        if (isset($data['content'][0]['text'])) {
            $text = $data['content'][0]['text'];
        }
        
        $tokensUsed = $data['usage']['output_tokens'] ?? 0;
        
        if (!$text) {
            return ['reply' => 'AI did not return a response.', 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => $tokensUsed];
        }
        
        // Parse response for reply and category
        $reply = '';
        $category = 'Support';
        $confidence = 0.85; // Claude typically provides high-quality responses
        
        if (preg_match('/Reply\s*:\s*(.+?)\s*Category\s*:/is', $text, $m)) {
            $reply = trim($m[1]);
        } else {
            $reply = trim($text);
        }
        
        if (preg_match('/Category\s*:\s*(Support|Sales|Spam|Billing|Feature Request)/i', $text, $m)) {
            $category = ucfirst(strtolower($m[1]));
            $confidence = 0.95; // Very high confidence when category is explicitly identified
        }
        
        return [
            'reply' => $reply,
            'category' => $category,
            'confidence' => $confidence,
            'tokens_used' => $tokensUsed
        ];
    }
    
    public function buildPrompt(string $message, string $tone, string $productName, array $context = []): string
    {
        $basePrompt = "You are a helpful customer support agent for {$productName}. Your responses should be {$tone} in tone.\n\n"
            . "Please respond to this customer message:\n\"{$message}\"\n\n"
            . "Provide your response in this exact format:\n"
            . "Reply: [your helpful response here]\n"
            . "Category: [Support|Sales|Spam|Billing|Feature Request]";
            
        // Add context if provided
        if (!empty($context['purchase_code'])) {
            $basePrompt .= "\n\nNote: This customer has a verified purchase code.";
        }
        
        if (!empty($context['previous_tickets'])) {
            $basePrompt .= "\n\nPrevious interactions: " . implode(', ', array_slice($context['previous_tickets'], -3));
        }
        
        return $basePrompt;
    }
    
    public function getConfig(): array
    {
        return [
            'api_key' => Env::get('CLAUDE_API_KEY', ''),
            'api_base' => Env::get('CLAUDE_API_BASE', 'https://api.anthropic.com'),
            'model' => Env::get('CLAUDE_MODEL', 'claude-3-haiku-20240307'),
            'temperature' => (float) Env::get('CLAUDE_TEMPERATURE', '0.3'),
            'max_tokens' => (int) Env::get('CLAUDE_MAX_TOKENS', '1000'),
            'timeout' => (int) Env::get('CLAUDE_TIMEOUT', '20'),
            
            // Configuration schema for admin interface
            'schema' => [
                'api_key' => [
                    'type' => 'password',
                    'label' => 'Claude API Key',
                    'required' => true,
                    'help' => 'Get your API key from Anthropic Console'
                ],
                'model' => [
                    'type' => 'select',
                    'label' => 'Model',
                    'options' => [
                        'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fast & Cost-effective)',
                        'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Balanced)',
                        'claude-3-opus-20240229' => 'Claude 3 Opus (Most Capable)'
                    ],
                    'default' => 'claude-3-haiku-20240307'
                ],
                'temperature' => [
                    'type' => 'range',
                    'label' => 'Temperature',
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.1,
                    'default' => 0.3,
                    'help' => 'Lower values = more focused, higher values = more creative'
                ],
                'max_tokens' => [
                    'type' => 'number',
                    'label' => 'Max Tokens',
                    'min' => 100,
                    'max' => 4000,
                    'default' => 1000,
                    'help' => 'Maximum length of AI response'
                ]
            ]
        ];
    }
    
    public function testConnection(): array
    {
        $apiKey = $this->config['api_key'];
        
        if (!$apiKey || strtoupper($apiKey) === 'MOCK_MODE') {
            return [
                'available' => false,
                'message' => 'Claude API key not configured'
            ];
        }
        
        // Test with a simple message
        $testPayload = json_encode([
            'model' => $this->config['model'],
            'max_tokens' => 50,
            'messages' => [
                ['role' => 'user', 'content' => 'Hello, can you confirm you are working?']
            ]
        ]);
        
        $ch = curl_init(rtrim($this->config['api_base'], '/') . '/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $testPayload,
            CURLOPT_TIMEOUT => 5,
        ]);
        
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($raw === false) {
            return [
                'available' => false,
                'message' => 'Connection failed: ' . curl_error($ch)
            ];
        }
        
        if ($code === 200) {
            return [
                'available' => true,
                'message' => 'Claude API connection successful'
            ];
        }
        
        return [
            'available' => false,
            'message' => "Claude API error (HTTP {$code})"
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Claude (Anthropic)',
            'version' => '1.0',
            'model' => $this->config['model'],
            'features' => [
                'chat_completion',
                'categorization',
                'multi_language',
                'safety_filtering',
                'long_context'
            ],
            'limits' => [
                'max_tokens' => 4000,
                'context_length' => '200K tokens',
                'rate_limit' => 'Varies by plan'
            ]
        ];
    }
    
    public function estimateTokens(string $prompt): int
    {
        // Claude uses approximately 3.5 characters per token
        return (int) ceil(strlen($prompt) / 3.5);
    }
}
