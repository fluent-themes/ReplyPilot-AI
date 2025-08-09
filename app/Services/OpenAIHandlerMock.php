<?php
namespace App\Services;

class OpenAIHandlerMock {
    public static function buildSmartPrompt(string $message, string $tone, string $productName): string
    {
        return OpenAIHandler::buildSmartPrompt($message, $tone, $productName);
    }

    public static function query(string $prompt): array
    {
        return [
            'reply'    => "This is a mock AI reply.\n\nPrompt snippet: " . substr($prompt, 0, 80),
            'category' => 'Mock',
        ];
    }
}
