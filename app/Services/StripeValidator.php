<?php
namespace App\Services;

use App\Contracts\LicenseValidatorInterface;

/**
 * Stripe payment verification validator (PLACEHOLDER - DISABLED)
 * Original functionality moved to extraFeatures/
 */
class StripeValidator implements LicenseValidatorInterface
{
    public function validate(string $code, array $options = []): array
    {
        return [
            'valid' => false,
            'product_name' => '',
            'error' => 'Stripe validation is currently disabled',
            'details' => []
        ];
    }
    
    public function testConnection(): array
    {
        return [
            'connected' => false,
            'message' => 'Stripe validation is disabled',
            'user_info' => []
        ];
    }
    
    public function getConfigSchema(): array
    {
        return [
            'disabled_notice' => [
                'type' => 'html',
                'content' => '<div class="alert alert-info">Stripe validation is currently disabled</div>'
            ]
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Stripe (Disabled)',
            'features' => [],
            'rate_limits' => []
        ];
    }
    
    public function getAvailableProducts(): array
    {
        return [];
    }
    
    public function validateConfig(array $config): array
    {
        return [
            'valid' => false,
            'errors' => ['Stripe validation is disabled']
        ];
    }
}
