<?php
namespace App\Factories;

use App\Contracts\LicenseValidatorInterface;
use App\Services\EnvatoValidator;
use App\Services\GumroadValidator;
use App\Services\StripeValidator;
use App\Services\MockLicenseValidator;
use App\Support\Settings;
use App\Helpers\ModeHelper;

/**
 * Factory for creating license validator instances (PARTIAL PLACEHOLDER)
 * Non-Envato validators are disabled, Envato validation remains functional
 */
class LicenseValidatorFactory
{
    /**
     * Available license validators
     */
    protected static array $validators = [
        'envato' => EnvatoValidator::class,
        'gumroad' => GumroadValidator::class, // Stubbed
        'stripe' => StripeValidator::class,   // Stubbed
        'mock' => MockLicenseValidator::class,
    ];
    
    /**
     * Create a license validator instance
     * 
     * @param string|null $validator Validator name, null for auto-detection
     * @return LicenseValidatorInterface
     * @throws \InvalidArgumentException
     */
    public static function create(?string $validator = null): LicenseValidatorInterface
    {
        // Force mock mode if requested
        if (ModeHelper::isMock()) {
            return new MockLicenseValidator();
        }
        
        // Auto-detect validator if not specified
        if ($validator === null) {
            $validator = self::detectValidator();
        }
        
        // Validate validator exists
        if (!isset(self::$validators[$validator])) {
            throw new \InvalidArgumentException("Unknown license validator: {$validator}");
        }
        
        $class = self::$validators[$validator];
        
        // Check if class exists
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("License validator class not found: {$class}");
        }
        
        return new $class();
    }
    
    /**
     * Auto-detect the configured validator
     * 
     * @return string Validator name
     */
    protected static function detectValidator(): string
    {
        $preferred = Settings::get('license_validator', 'envato');
        
        // For non-Envato validators, force fallback to Envato
        if (in_array($preferred, ['gumroad', 'stripe'])) {
            error_log("LicenseValidatorFactory: {$preferred} validator is disabled, falling back to envato");
            $preferred = 'envato';
        }
        
        // Check if preferred validator is available
        if (self::isValidatorAvailable($preferred)) {
            return $preferred;
        }
        
        // Fallback order: envato -> mock (skip disabled validators)
        if (self::isValidatorAvailable('envato')) {
            return 'envato';
        }
        
        // Final fallback to mock
        return 'mock';
    }
    
    /**
     * Check if a validator is available and configured
     * 
     * @param string $validator Validator name
     * @return bool
     */
    public static function isValidatorAvailable(string $validator): bool
    {
        if (!isset(self::$validators[$validator])) {
            return false;
        }
        
        // Non-Envato validators are marked as unavailable
        if (in_array($validator, ['gumroad', 'stripe'])) {
            return false;
        }
        
        $class = self::$validators[$validator];
        if (!class_exists($class)) {
            return false;
        }
        
        try {
            $instance = new $class();
            $test = $instance->testConnection();
            return $test['connected'] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get list of all available validators
     * 
     * @return array Validator information
     */
    public static function getAvailableValidators(): array
    {
        $result = [];
        
        foreach (self::$validators as $name => $class) {
            if ($name === 'mock') continue; // Skip mock in production list
            
            // Mark non-Envato validators as disabled
            $available = !in_array($name, ['gumroad', 'stripe']) && self::isValidatorAvailable($name);
            $disabled = in_array($name, ['gumroad', 'stripe']);
            
            $info = null;
            if (class_exists($class)) {
                $providerInfo = (new $class())->getProviderInfo();
                if ($disabled) {
                    $providerInfo['name'] .= ' (Disabled)';
                    $providerInfo['features'] = [];
                }
                $info = $providerInfo;
            }
            
            $result[$name] = [
                'name' => $name,
                'class' => $class,
                'available' => $available,
                'disabled' => $disabled,
                'info' => $info
            ];
        }
        
        return $result;
    }
    
    /**
     * Register a new license validator
     * 
     * @param string $name Validator name
     * @param string $class Validator class
     */
    public static function register(string $name, string $class): void
    {
        if (!is_subclass_of($class, LicenseValidatorInterface::class)) {
            throw new \InvalidArgumentException("Class must implement LicenseValidatorInterface");
        }
        
        self::$validators[$name] = $class;
    }
    
    /**
     * Get validator configuration schemas for admin interface
     * 
     * @return array Configuration schemas for all validators
     */
    public static function getConfigSchemas(): array
    {
        $schemas = [];
        
        foreach (self::$validators as $name => $class) {
            if ($name === 'mock') continue;
            
            if (class_exists($class)) {
                $instance = new $class();
                $schema = $instance->getConfigSchema();
                
                // Add disabled notice for non-Envato validators
                if (in_array($name, ['gumroad', 'stripe'])) {
                    $schema = [
                        'disabled_notice' => [
                            'type' => 'html',
                            'content' => '<div class="alert alert-warning">' . ucfirst($name) . ' validation is currently disabled</div>'
                        ]
                    ] + $schema;
                }
                
                $schemas[$name] = $schema;
            }
        }
        
        return $schemas;
    }
}
