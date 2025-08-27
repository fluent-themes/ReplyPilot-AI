<?php
namespace App\Services;

use App\Contracts\LicenseValidatorInterface;
use App\Support\Settings;

/**
 * Gumroad license validator
 * PLACEHOLDER: Gumroad validation disabled - returns "disabled" responses
 */
class GumroadValidator implements LicenseValidatorInterface
{
    public function validate(string $code, array $options = []): array
    {
        // Gumroad validation is disabled
        return [
            'valid' => false,
            'product_name' => '',
            'error' => 'Gumroad validation is currently disabled',
            'details' => []
        ];
    }
    
    public function testConnection(): array
    {
        // Connection testing is disabled
        return [
            'connected' => false,
            'message' => 'Gumroad validation is currently disabled',
            'user_info' => []
        ];
    }
    
    public function getConfigSchema(): array
    {
        return [
            'access_token' => [
                'type' => 'password',
                'label' => 'Gumroad Access Token (Currently Disabled)',
                'required' => false,
                'help' => 'Gumroad validation functionality is temporarily disabled',
                'disabled' => true,
                'secure' => true
            ],
            'product_permalink' => [
                'type' => 'text',
                'label' => 'Product Permalink (Currently Disabled)',
                'required' => false,
                'help' => 'Gumroad validation functionality is temporarily disabled',
                'disabled' => true,
                'placeholder' => 'disabled'
            ],
            'validation_enabled' => [
                'type' => 'checkbox',
                'label' => 'Enable License Validation (Currently Disabled)',
                'default' => false,
                'disabled' => true
            ]
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Gumroad',
            'features' => [],
            'rate_limits' => [
                'requests_per_minute' => 0,
                'requests_per_day' => 0
            ],
            'status' => 'disabled'
        ];
    }
    
    public function getAvailableProducts(): array
    {
        // Product listing is disabled
        return [];
    }
    
    public function validateConfig(array $config): array
    {
        // Config validation always succeeds but marks as disabled
        return [
            'valid' => true,
            'errors' => [],
            'warning' => 'Gumroad validation is temporarily disabled'
        ];
    }
}
