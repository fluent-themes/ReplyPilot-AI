<?php
namespace App\Contracts;

/**
 * Interface for AI service providers (OpenAI, Claude, Gemini, etc.)
 */
interface AIProviderInterface
{
    /**
     * Query the AI service with a prompt
     * 
     * @param string $prompt The formatted prompt
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array ['reply' => string, 'category' => string, 'confidence' => float, 'tokens_used' => int]
     */
    public function query(string $prompt, array $options = []): array;
    
    /**
     * Build a smart prompt for the specific AI provider
     * 
     * @param string $message User message
     * @param string $tone Response tone
     * @param string $productName Product context
     * @param array $context Additional context
     * @return string Formatted prompt
     */
    public function buildPrompt(string $message, string $tone, string $productName, array $context = []): string;
    
    /**
     * Get provider-specific configuration
     * 
     * @return array Configuration options and defaults
     */
    public function getConfig(): array;
    
    /**
     * Test if the provider is available and configured
     * 
     * @return array ['available' => bool, 'message' => string]
     */
    public function testConnection(): array;
    
    /**
     * Get provider name and version
     * 
     * @return array ['name' => string, 'version' => string, 'model' => string]
     */
    public function getProviderInfo(): array;
    
    /**
     * Estimate token usage for a prompt
     * 
     * @param string $prompt
     * @return int Estimated token count
     */
    public function estimateTokens(string $prompt): int;
}
