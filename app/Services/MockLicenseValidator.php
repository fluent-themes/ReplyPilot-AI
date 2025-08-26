<?php
namespace App\Services;

use App\Contracts\LicenseValidatorInterface;

/**
 * Mock license validator for testing
 */
class MockLicenseValidator implements LicenseValidatorInterface
{
    public function validate(string $code, array $options = []): array
    {
        // Mock validation logic
        if ($code === 'valid-code-123') {
            return [
                'valid' => true,
                'product_name' => 'Mock Product',
                'error' => null,
                'details' => [
                    'item_id' => 123456,
                    'buyer_username' => 'mock_buyer',
                    'purchase_date' => date('Y-m-d'),
                    'license' => 'regular'
                ]
            ];
        }
        
        return [
            'valid' => false,
            'product_name' => '',
            'error' => 'Invalid mock purchase code',
            'details' => []
        ];
    }
    
    public function testConnection(): array
    {
        return [
            'connected' => true,
            'message' => 'Mock validator is always connected',
            'user_info' => [
                'username' => 'mock_user',
                'email' => 'mock@example.com'
            ]
        ];
    }
    
    public function getConfigSchema(): array
    {
        return [
            'mock_mode' => [
                'type' => 'info',
                'label' => 'Mock Mode',
                'help' => 'This is a mock validator for testing. Use "valid-code-123" as valid code.'
            ]
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Mock Validator',
            'features' => ['testing', 'development'],
            'rate_limits' => []
        ];
    }
    
    public function getAvailableProducts(): array
    {
        return [
            ['id' => 123456, 'name' => 'Mock Product 1'],
            ['id' => 789012, 'name' => 'Mock Product 2']
        ];
    }
    
    public function validateConfig(array $config): array
    {
        return [
            'valid' => true,
            'errors' => []
        ];
    }
}
