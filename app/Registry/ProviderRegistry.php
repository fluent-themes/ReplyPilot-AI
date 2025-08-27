<?php
namespace App\Registry;

use App\Support\Settings;
use App\Factories\AIProviderFactory;
use App\Factories\LicenseValidatorFactory;

/**
 * Configuration registry for managing providers and settings
 */
class ProviderRegistry
{
    /**
     * Get all configured providers with their status
     * 
     * @return array Complete provider configuration
     */
    public static function getConfiguration(): array
    {
        return [
            'ai_providers' => self::getAIProviderConfiguration(),
            'license_validators' => self::getLicenseValidatorConfiguration(),
            'active_providers' => self::getActiveProviders(),
            'system_settings' => self::getSystemSettings()
        ];
    }
    
    /**
     * Get AI provider configuration
     */
    protected static function getAIProviderConfiguration(): array
    {
        $available = AIProviderFactory::getAvailableProviders();
        $schemas = AIProviderFactory::getConfigSchemas();
        $active = Settings::get('ai_provider', 'openai');
        
        $config = [];
        foreach ($available as $name => $info) {
            $config[$name] = [
                'name' => $name,
                'display_name' => ucfirst($name),
                'available' => $info['available'],
                'active' => $name === $active,
                'info' => $info['info'],
                'schema' => $schemas[$name] ?? [],
                'settings' => self::getProviderSettings('ai', $name)
            ];
        }
        
        return $config;
    }
    
    /**
     * Get license validator configuration
     */
    protected static function getLicenseValidatorConfiguration(): array
    {
        $available = LicenseValidatorFactory::getAvailableValidators();
        $schemas = LicenseValidatorFactory::getConfigSchemas();
        $active = Settings::get('license_validator', 'envato');
        
        $config = [];
        foreach ($available as $name => $info) {
            $config[$name] = [
                'name' => $name,
                'display_name' => ucfirst($name),
                'available' => $info['available'],
                'active' => $name === $active,
                'info' => $info['info'],
                'schema' => $schemas[$name] ?? [],
                'settings' => self::getProviderSettings('license', $name)
            ];
        }
        
        return $config;
    }
    
    /**
     * Get settings for a specific provider
     */
    protected static function getProviderSettings(string $type, string $provider): array
    {
        $settings = [];
        $prefix = $provider . '_';
        
        // Get regular settings
        $allSettings = Settings::get('*', []); // Assuming Settings supports wildcard
        foreach ($allSettings as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $settings[substr($key, strlen($prefix))] = $value;
            }
        }
        
        // Note: Secure settings are not included for security reasons
        
        return $settings;
    }
    
    /**
     * Get currently active providers
     */
    protected static function getActiveProviders(): array
    {
        return [
            'ai' => Settings::get('ai_provider', 'openai'),
            'license' => Settings::get('license_validator', 'envato')
        ];
    }
    
    /**
     * Get system-wide settings
     */
    protected static function getSystemSettings(): array
    {
        return [
            'purchase_validation_enabled' => Settings::get('purchase_validation_enabled', false),
            'purchase_code_enabled' => Settings::get('purchase_code_enabled', false),
            'purchase_code_required' => Settings::get('purchase_code_required', false),
            'ai_categorization_enabled' => Settings::get('ai_categorization_enabled', true),
            'ajax_rate_limit' => Settings::get('ajax_rate_limit', 6),
            'session_timeout' => Settings::get('session_timeout', 3600),
            'ai_token_limit' => Settings::get('ai_token_limit', 1000),
            'mail_transport' => Settings::get('mail_transport', 'smtp'),
            'mail_from_name' => Settings::get('mail_from_name', 'ReplyPilot AI'),
            'mail_from_address' => Settings::get('mail_from_address', 'noreply@example.com')
        ];
    }
    
    /**
     * Validate provider configuration
     */
    public static function validateConfiguration(string $type, string $provider, array $config): array
    {
        try {
            if ($type === 'ai') {
                $instance = AIProviderFactory::create($provider);
                return $instance->validateConfig($config);
            } elseif ($type === 'license') {
                $instance = LicenseValidatorFactory::create($provider);
                return $instance->validateConfig($config);
            }
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'errors' => ['Configuration validation failed: ' . $e->getMessage()]
            ];
        }
        
        return ['valid' => false, 'errors' => ['Unknown provider type']];
    }
    
    /**
     * Update provider configuration
     */
    public static function updateProviderConfiguration(string $type, string $provider, array $config): bool
    {
        try {
            // Validate configuration first
            $validation = self::validateConfiguration($type, $provider, $config);
            if (!$validation['valid']) {
                return false;
            }
            
            // Save settings
            foreach ($config as $key => $value) {
                $settingKey = $provider . '_' . $key;
                
                // Determine if setting should be encrypted
                if (in_array($key, ['api_key', 'personal_token', 'secret', 'password'])) {
                    Settings::setSecure($settingKey, $value);
                } else {
                    Settings::set($settingKey, $value);
                }
            }
            
            return true;
        } catch (\Throwable $e) {
            error_log('Failed to update provider configuration: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get health check for all providers
     */
    public static function getHealthCheck(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'ai_providers' => [],
            'license_validators' => [],
            'issues' => []
        ];
        
        // Check AI providers
        foreach (AIProviderFactory::getAvailableProviders() as $name => $info) {
            $status = 'unknown';
            $message = 'Not tested';
            
            try {
                $instance = AIProviderFactory::create($name);
                $test = $instance->testConnection();
                $status = $test['available'] ? 'healthy' : 'unhealthy';
                $message = $test['message'];
            } catch (\Throwable $e) {
                $status = 'error';
                $message = $e->getMessage();
                $health['issues'][] = "AI Provider {$name}: {$message}";
            }
            
            $health['ai_providers'][$name] = [
                'status' => $status,
                'message' => $message
            ];
        }
        
        // Check license validators
        foreach (LicenseValidatorFactory::getAvailableValidators() as $name => $info) {
            $status = 'unknown';
            $message = 'Not tested';
            
            try {
                $instance = LicenseValidatorFactory::create($name);
                $test = $instance->testConnection();
                $status = $test['connected'] ? 'healthy' : 'unhealthy';
                $message = $test['message'];
            } catch (\Throwable $e) {
                $status = 'error';
                $message = $e->getMessage();
                $health['issues'][] = "License Validator {$name}: {$message}";
            }
            
            $health['license_validators'][$name] = [
                'status' => $status,
                'message' => $message
            ];
        }
        
        // Determine overall status
        if (!empty($health['issues'])) {
            $health['overall_status'] = 'degraded';
        }
        
        return $health;
    }
    
    /**
     * Export configuration for backup/migration
     */
    public static function exportConfiguration(): array
    {
        $export = [
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'configuration' => self::getConfiguration()
        ];
        
        // Remove sensitive data
        foreach ($export['configuration']['ai_providers'] as &$provider) {
            unset($provider['settings']['api_key']);
            unset($provider['settings']['secret']);
        }
        
        foreach ($export['configuration']['license_validators'] as &$validator) {
            unset($validator['settings']['personal_token']);
            unset($validator['settings']['api_key']);
        }
        
        return $export;
    }
    
    /**
     * Import configuration from backup
     */
    public static function importConfiguration(array $config): bool
    {
        try {
            if (!isset($config['configuration'])) {
                throw new \InvalidArgumentException('Invalid configuration format');
            }
            
            $configuration = $config['configuration'];
            
            // Import system settings
            if (isset($configuration['system_settings'])) {
                foreach ($configuration['system_settings'] as $key => $value) {
                    Settings::set($key, $value);
                }
            }
            
            // Import active providers
            if (isset($configuration['active_providers'])) {
                foreach ($configuration['active_providers'] as $type => $provider) {
                    Settings::set($type . '_provider', $provider);
                }
            }
            
            return true;
        } catch (\Throwable $e) {
            error_log('Failed to import configuration: ' . $e->getMessage());
            return false;
        }
    }
}
