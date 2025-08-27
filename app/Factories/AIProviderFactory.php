<?php
namespace App\Factories;

use App\Contracts\AIProviderInterface;
use App\Services\OpenAIProvider;
use App\Services\ClaudeProvider;
use App\Services\GeminiProvider;
use App\Services\MockAIProvider;
use App\Support\Settings;
use App\Helpers\ModeHelper;

/**
 * Factory for creating AI provider instances
 */
class AIProviderFactory
{
    /**
     * Available AI providers
     */
    protected static array $providers = [
        'openai' => OpenAIProvider::class,
        'claude' => ClaudeProvider::class,
        'gemini' => GeminiProvider::class,
        'mock' => MockAIProvider::class,
    ];
    
    /**
     * Create an AI provider instance
     * 
     * @param string|null $provider Provider name, null for auto-detection
     * @return AIProviderInterface
     * @throws \InvalidArgumentException
     */
    public static function create(?string $provider = null): AIProviderInterface
    {
        // Force mock mode if requested
        if (ModeHelper::isMock()) {
            return new MockAIProvider();
        }
        
        // Auto-detect provider if not specified
        if ($provider === null) {
            $provider = self::detectProvider();
        }
        
        // Validate provider exists
        if (!isset(self::$providers[$provider])) {
            throw new \InvalidArgumentException("Unknown AI provider: {$provider}");
        }
        
        $class = self::$providers[$provider];
        
        // Check if class exists
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("AI provider class not found: {$class}");
        }
        
        return new $class();
    }
    
    /**
     * Auto-detect the best available provider
     * 
     * @return string Provider name
     */
    protected static function detectProvider(): string
    {
        $preferred = Settings::get('ai_provider', 'openai');
        
        // Check if preferred provider is available
        if (self::isProviderAvailable($preferred)) {
            return $preferred;
        }
        
        // Fallback to first available provider
        foreach (array_keys(self::$providers) as $provider) {
            if ($provider !== 'mock' && self::isProviderAvailable($provider)) {
                return $provider;
            }
        }
        
        // Final fallback to mock
        return 'mock';
    }
    
    /**
     * Check if a provider is available and configured
     * 
     * @param string $provider Provider name
     * @return bool
     */
    public static function isProviderAvailable(string $provider): bool
    {
        if (!isset(self::$providers[$provider])) {
            return false;
        }
        
        $class = self::$providers[$provider];
        if (!class_exists($class)) {
            return false;
        }
        
        try {
            $instance = new $class();
            $test = $instance->testConnection();
            return $test['available'] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get list of all available providers
     * 
     * @return array Provider information
     */
    public static function getAvailableProviders(): array
    {
        $result = [];
        
        foreach (self::$providers as $name => $class) {
            if ($name === 'mock') continue; // Skip mock in production list
            
            $result[$name] = [
                'name' => $name,
                'class' => $class,
                'available' => self::isProviderAvailable($name),
                'info' => class_exists($class) ? (new $class())->getProviderInfo() : null
            ];
        }
        
        return $result;
    }
    
    /**
     * Register a new AI provider
     * 
     * @param string $name Provider name
     * @param string $class Provider class
     */
    public static function register(string $name, string $class): void
    {
        if (!is_subclass_of($class, AIProviderInterface::class)) {
            throw new \InvalidArgumentException("Class must implement AIProviderInterface");
        }
        
        self::$providers[$name] = $class;
    }
    
    /**
     * Get provider configuration schema for admin interface
     * 
     * @return array Configuration schemas for all providers
     */
    public static function getConfigSchemas(): array
    {
        $schemas = [];
        
        foreach (self::$providers as $name => $class) {
            if ($name === 'mock') continue;
            
            if (class_exists($class)) {
                $instance = new $class();
                $schemas[$name] = $instance->getConfig();
            }
        }
        
        return $schemas;
    }
}
