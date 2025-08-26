<?php
namespace App\Support;

class MailerMock {
    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        $dir = __DIR__ . '/../../storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . '/' . uniqid('mail_', true) . '.eml';
        $content = "To: $to\nSubject: $subject\n\n" . $html;
        file_put_contents($file, $content);
        return true;
    }
}
