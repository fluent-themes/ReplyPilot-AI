<?php
namespace App\Services;

use App\Core\Env;

class OpenAIHandler {
    public static function buildSmartPrompt(string $message, string $tone, string $productName): string
    {
        return "You are a courteous support agent for {$productName}. Keep the tone {$tone}. Reply to the user message below:\n" .
            "User: {$message}\n\n" .
            "Format:\n" .
            "Reply: <your reply>\n" .
            "Category: <Support|Sales|Spam>";
    }

    public static function query(string $prompt): array
    {
        $apiKey = Env::get('OPENAI_API_KEY');
        // Real call would go here. For now, return dummy.
        return [
            'reply'    => 'Thank you for reaching out. We will get back shortly.',
            'category' => 'Support',
        ];
    }
}
