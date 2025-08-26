<?php
namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\Core\Env;

/**
 * OpenAI provider implementation
 */
class OpenAIProvider implements AIProviderInterface
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
        $temperature = $options['temperature'] ?? $this->config['temperature'];
        $maxTokens = $options['max_tokens'] ?? $this->config['max_tokens'];
        
        if (!$apiKey || strtoupper($apiKey) === 'MOCK_MODE') {
            return [
                'reply' => 'OpenAI API key not configured',
                'category' => 'Support',
                'confidence' => 0.0,
                'tokens_used' => 0
            ];
        }
        
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that outputs a reply and a category.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);

        $ch = curl_init(rtrim($this->config['api_base'], '/') . '/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
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
            error_log('OpenAI cURL error: ' . $err);
            curl_close($ch);
            return ['reply' => 'AI error: ' . $err, 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => 0];
        }
        
        if ($code !== 200) {
            error_log('OpenAI HTTP ' . $code . ' response: ' . substr($raw, 0, 1000));
            curl_close($ch);
            return ['reply' => 'AI service unavailable (HTTP ' . $code . ').', 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => 0];
        }
        
        curl_close($ch);
        $data = json_decode($raw, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;
        
        if (!$text) {
            return ['reply' => 'AI did not return a response.', 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => $tokensUsed];
        }
        
        // Parse "Reply:" and "Category:" from the text
        $reply = '';
        $category = 'Support';
        $confidence = 0.8; // Default confidence for OpenAI
        
        if (preg_match('/Reply\s*:\s*(.+?)\s*Category\s*:/is', $text, $m)) {
            $reply = trim($m[1]);
        } else {
            $reply = trim($text);
        }
        
        if (preg_match('/Category\s*:\s*(Support|Sales|Spam|Billing|Feature Request)/i', $text, $m)) {
            $category = ucfirst(strtolower($m[1]));
            $confidence = 0.9; // Higher confidence when category is explicitly identified
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
        $basePrompt = "You are a courteous support agent for {$productName}. Keep the tone {$tone}. Reply to the user message below:\n"
            . "User: {$message}\n\n"
            . "Format:\n"
            . "Reply: <your reply>\n"
            . "Category: <Support|Sales|Spam|Billing|Feature Request>";
            
        // Add context if provided
        if (!empty($context['purchase_code'])) {
            $basePrompt .= "\n\nNote: This user has a valid purchase code.";
        }
        
        if (!empty($context['previous_tickets'])) {
            $basePrompt .= "\n\nPrevious interactions: " . implode(', ', $context['previous_tickets']);
        }
        
        return $basePrompt;
    }
    
    public function getConfig(): array
    {
        return [
            'api_key' => Env::get('OPENAI_API_KEY', ''),
            'api_base' => Env::get('OPENAI_API_BASE', 'https://api.openai.com'),
            'model' => Env::get('OPENAI_MODEL', 'gpt-5-nano'),
            'temperature' => (float) Env::get('OPENAI_TEMPERATURE', '0.3'),
            'max_tokens' => (int) Env::get('OPENAI_MAX_TOKENS', '1000'),
            'timeout' => (int) Env::get('OPENAI_TIMEOUT', '20'),
            
            // Configuration schema for admin interface
            'schema' => [
                'api_key' => [
                    'type' => 'password',
                    'label' => 'OpenAI API Key',
                    'required' => true,
                    'help' => 'Get your API key from https://platform.openai.com'
                ],
                'model' => [
                    'type' => 'select',
                    'label' => 'Model',
                    'options' => [
                        'gpt-5-nano' => 'GPT-4o Mini (Recommended)',
                        'gpt-4o' => 'GPT-4o (Higher Quality)',
                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Budget)'
                    ],
                    'default' => 'gpt-5-nano'
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
                'message' => 'OpenAI API key not configured'
            ];
        }
        
        // Test with a simple prompt
        $ch = curl_init(rtrim($this->config['api_base'], '/') . '/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
            ],
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
                'message' => 'OpenAI API connection successful'
            ];
        }
        
        return [
            'available' => false,
            'message' => "OpenAI API error (HTTP {$code})"
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'OpenAI',
            'version' => '1.0',
            'model' => $this->config['model'],
            'features' => [
                'chat_completion',
                'categorization',
                'multi_language',
                'token_counting'
            ],
            'limits' => [
                'max_tokens' => 4000,
                'rate_limit' => '60 req/min'
            ]
        ];
    }
    
    public function estimateTokens(string $prompt): int
    {
        // Rough estimation: 1 token ≈ 4 characters for English
        return (int) ceil(strlen($prompt) / 4);
    }
}
