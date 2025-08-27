<?php
namespace App\Services;

use App\Contracts\AIProviderInterface;

/**
 * Mock AI provider for testing and development
 */
class MockAIProvider implements AIProviderInterface
{
    public function query(string $prompt, array $options = []): array
    {
        return [
            'reply' => "This is a mock AI reply.\n\nPrompt snippet: " . substr($prompt, 0, 80),
            'category' => 'Mock',
            'confidence' => 1.0,
            'tokens_used' => $this->estimateTokens($prompt)
        ];
    }
    
    public function buildPrompt(string $message, string $tone, string $productName, array $context = []): string
    {
        return "Mock prompt for {$productName} with {$tone} tone: {$message}";
    }
    
    public function getConfig(): array
    {
        return [
            'name' => 'Mock AI Provider',
            'enabled' => true,
            'schema' => [
                'mock_mode' => [
                    'type' => 'info',
                    'label' => 'Mock Mode',
                    'help' => 'This is a mock provider for testing purposes'
                ]
            ]
        ];
    }
    
    public function testConnection(): array
    {
        return [
            'available' => true,
            'message' => 'Mock provider is always available'
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Mock Provider',
            'version' => '1.0',
            'model' => 'mock-model',
            'features' => ['testing', 'development'],
            'limits' => []
        ];
    }
    
    public function estimateTokens(string $prompt): int
    {
        return (int) ceil(strlen($prompt) / 4);
    }
}
