<?php
namespace App\Contracts;

/**
 * Interface for license validation services (Envato, Gumroad, etc.)
 */
interface LicenseValidatorInterface
{
    /**
     * Validate a purchase code
     * 
     * @param string $code Purchase/license code
     * @param array $options Additional validation options
     * @return array ['valid' => bool, 'product_name' => string, 'error' => string|null, 'details' => array]
     */
    public function validate(string $code, array $options = []): array;
    
    /**
     * Test API connection and credentials
     * 
     * @return array ['connected' => bool, 'message' => string, 'user_info' => array]
     */
    public function testConnection(): array;
    
    /**
     * Get provider configuration requirements
     * 
     * @return array Configuration schema and requirements
     */
    public function getConfigSchema(): array;
    
    /**
     * Get provider name and supported features
     * 
     * @return array ['name' => string, 'features' => array, 'rate_limits' => array]
     */
    public function getProviderInfo(): array;
    
    /**
     * Get list of available products/items (if supported)
     * 
     * @return array List of products with IDs and names
     */
    public function getAvailableProducts(): array;
    
    /**
     * Validate configuration settings
     * 
     * @param array $config Configuration to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateConfig(array $config): array;
}
