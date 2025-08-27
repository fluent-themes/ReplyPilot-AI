<?php
namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\Core\Env;

/**
 * Google Gemini AI provider implementation
 */
class GeminiProvider implements AIProviderInterface
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
                'reply' => 'Gemini API key not configured',
                'category' => 'Support',
                'confidence' => 0.0,
                'tokens_used' => 0
            ];
        }
        
        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'topP' => 0.8,
                'topK' => 10
            ]
        ]);

        $baseUrl = rtrim($this->config['api_base'], '/');
        $url = "{$baseUrl}/v1/models/{$model}:generateContent?key={$apiKey}";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
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
            error_log('Gemini cURL error: ' . $err);
            curl_close($ch);
            return ['reply' => 'AI error: ' . $err, 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => 0];
        }
        
        if ($code !== 200) {
            error_log('Gemini HTTP ' . $code . ' response: ' . substr($raw, 0, 1000));
            curl_close($ch);
            return ['reply' => 'AI service unavailable (HTTP ' . $code . ').', 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => 0];
        }
        
        curl_close($ch);
        $data = json_decode($raw, true);
        
        $text = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        // Estimate tokens used (Gemini doesn't return token count directly)
        $tokensUsed = $this->estimateTokens($text);
        
        if (!$text) {
            return ['reply' => 'AI did not return a response.', 'category' => 'Support', 'confidence' => 0.0, 'tokens_used' => $tokensUsed];
        }
        
        // Parse response for reply and category
        $reply = '';
        $category = 'Support';
        $confidence = 0.8; // Gemini provides good quality responses
        
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
        $basePrompt = "You are a helpful customer support assistant for {$productName}. "
            . "Please provide a {$tone} response to the following customer message.\n\n"
            . "Customer message: \"{$message}\"\n\n"
            . "Instructions:\n"
            . "1. Provide a helpful and {$tone} response\n"
            . "2. Categorize the message appropriately\n\n"
            . "Format your response exactly like this:\n"
            . "Reply: [your response here]\n"
            . "Category: [Support|Sales|Spam|Billing|Feature Request]";
            
        // Add context if provided
        if (!empty($context['purchase_code'])) {
            $basePrompt .= "\n\nNote: This customer has a valid purchase code.";
        }
        
        if (!empty($context['previous_tickets'])) {
            $basePrompt .= "\n\nPrevious customer interactions: " . implode(', ', array_slice($context['previous_tickets'], -2));
        }
        
        return $basePrompt;
    }
    
    public function getConfig(): array
    {
        return [
            'api_key' => Env::get('GEMINI_API_KEY', ''),
            'api_base' => Env::get('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com'),
            'model' => Env::get('GEMINI_MODEL', 'gemini-1.5-flash'),
            'temperature' => (float) Env::get('GEMINI_TEMPERATURE', '0.4'),
            'max_tokens' => (int) Env::get('GEMINI_MAX_TOKENS', '1000'),
            'timeout' => (int) Env::get('GEMINI_TIMEOUT', '20'),
            
            // Configuration schema for admin interface
            'schema' => [
                'api_key' => [
                    'type' => 'password',
                    'label' => 'Gemini API Key',
                    'required' => true,
                    'help' => 'Get your API key from Google AI Studio'
                ],
                'model' => [
                    'type' => 'select',
                    'label' => 'Model',
                    'options' => [
                        'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast & Efficient)',
                        'gemini-1.5-pro' => 'Gemini 1.5 Pro (Advanced Reasoning)',
                        'gemini-1.0-pro' => 'Gemini 1.0 Pro (Reliable)'
                    ],
                    'default' => 'gemini-1.5-flash'
                ],
                'temperature' => [
                    'type' => 'range',
                    'label' => 'Temperature',
                    'min' => 0,
                    'max' => 2,
                    'step' => 0.1,
                    'default' => 0.4,
                    'help' => 'Controls randomness in responses'
                ],
                'max_tokens' => [
                    'type' => 'number',
                    'label' => 'Max Output Tokens',
                    'min' => 100,
                    'max' => 8192,
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
                'message' => 'Gemini API key not configured'
            ];
        }
        
        // Test with a simple message
        $testPayload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Hello! Please respond with just "Test successful" to confirm connectivity.']
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 10
            ]
        ]);
        
        $baseUrl = rtrim($this->config['api_base'], '/');
        $url = "{$baseUrl}/v1/models/{$this->config['model']}:generateContent?key={$apiKey}";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
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
                'message' => 'Gemini API connection successful'
            ];
        }
        
        return [
            'available' => false,
            'message' => "Gemini API error (HTTP {$code})"
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Google Gemini',
            'version' => '1.0',
            'model' => $this->config['model'],
            'features' => [
                'chat_completion',
                'categorization',
                'multi_language',
                'multimodal',
                'code_generation'
            ],
            'limits' => [
                'max_tokens' => 8192,
                'context_length' => '1M tokens (1.5 models)',
                'rate_limit' => '1500 RPD (free tier)'
            ]
        ];
    }
    
    public function estimateTokens(string $prompt): int
    {
        // Gemini uses approximately 4 characters per token for English
        return (int) ceil(strlen($prompt) / 4);
    }
}
