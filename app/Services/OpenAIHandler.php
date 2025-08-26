<?php
namespace App\Services;

use App\Core\Env;

class OpenAIHandler {
    public static function buildSmartPrompt(string $message, string $tone, string $productName): string
    {
        return "You are a courteous support agent for {$productName}. Keep the tone {$tone}. Reply to the user message below:\n"
            . "User: {$message}\n\n"
            . "Format:\n"
            . "Reply: <your reply>\n"
            . "Category: <Support|Sales|Spam>";
    }

    public static function query(string $prompt): array
    {
        $apiKey = Env::get('OPENAI_API_KEY');
        $model = Env::get('OPENAI_MODEL', 'gpt-5-nano');
        if ((defined('RPAI_MOCK_MODE') && RPAI_MOCK_MODE) || !$apiKey || strtoupper($apiKey) === 'MOCK_MODE') {
            return [
                'reply'    => 'Thank you for reaching out. We will get back shortly.',
                'category' => 'Support',
            ];
        }
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that outputs a reply and a category.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ]);

        $ch = curl_init(rtrim(Env::get('OPENAI_API_BASE', 'https://api.openai.com'), '/') . '/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($raw === false) {
            $err = curl_error($ch);
            error_log('OpenAI cURL error: ' . $err);
            curl_close($ch);
            return ['reply' => 'AI error: ' . $err, 'category' => 'Support'];
        }
        if ($code !== 200) {
            error_log('OpenAI HTTP ' . $code . ' response: ' . substr($raw, 0, 1000));
            curl_close($ch);
            return ['reply' => 'AI service unavailable (HTTP ' . $code . ').', 'category' => 'Support'];
        }
        curl_close($ch);
        $data = json_decode($raw, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        if (!$text) {
            return ['reply' => 'AI did not return a response.', 'category' => 'Support'];
        }
        // Parse "Reply:" and "Category:" from the text
        $reply = '';
        $category = 'Support';
        if (preg_match('/Reply\s*:\s*(.+?)\s*Category\s*:/is', $text, $m)) {
            $reply = trim($m[1]);
        } else {
            $reply = trim($text);
        }
        if (preg_match('/Category\s*:\s*(Support|Sales|Spam)/i', $text, $m)) {
            $category = ucfirst(strtolower($m[1]));
        }
        return ['reply' => $reply, 'category' => $category];
    }
}
