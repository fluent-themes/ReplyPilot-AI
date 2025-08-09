
<?php namespace App\Services;
use App\Core\Env;

class OpenAIHandler {
    public static function buildSmartPrompt($message, $tone, $productName){
        return """You are a courteous support agent for {$productName}. Keep the tone {$tone}. Reply to the user message below:
User: {$message}

Format:
Reply: <your reply>
Category: <Support|Sales|Spam>
""";
    }
    public static function query($prompt){
        $apiKey = Env::get('OPENAI_API_KEY');
        // Real call would go here. For now, return dummy.
        return [
            'reply'    => 'Thank you for reaching out. We will get back shortly.',
            'category' => 'Support'
        ];
    }
}
?>
