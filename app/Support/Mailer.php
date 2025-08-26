<?php
namespace App\Support;

use App\Core\Env;

class Mailer {
    public function __construct() {}

    protected function bootstrapPHPMailer(): bool
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }
        // Try to include PHPMailer manually if no composer autoload
        $base = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/';
        $candidates = ['Exception.php','PHPMailer.php','SMTP.php'];
        foreach ($candidates as $f) {
            $path = $base . $f;
            if (file_exists($path)) {
                require_once $path;
            }
        }
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        // Validate email address
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid email address provided to mailer');
            return false;
        }
        
        // Sanitize subject and from name to prevent header injection
        $subject = str_replace(["\r", "\n"], '', $subject);
        
        $from = Env::get('MAIL_FROM_ADDRESS');
        $fromName = Env::get('MAIL_FROM_NAME');
        if ($fromName) {
            $fromName = str_replace(["\r", "\n"], '', $fromName);
        }

        // Prefer PHPMailer via SMTP if available
        if ($this->bootstrapPHPMailer()) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Timeout = 10;
                $mail->Host       = Env::get('SMTP_HOST', '');
                $mail->Port       = (int) Env::get('SMTP_PORT', 587);
                $mail->SMTPAuth   = Env::get('SMTP_AUTH', 'true') !== 'false';
                $mail->Username   = Env::get('SMTP_USERNAME', '');
                $mail->Password   = Env::get('SMTP_PASS', '');
                $encryption       = strtolower((string) Env::get('SMTP_ENCRYPTION', 'tls'));
                if ($encryption === 'ssl') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($encryption === 'tls') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } // else: none

                if ($from) {
                    $mail->setFrom($from, $fromName ?: '');
                    $mail->addReplyTo($from, $fromName ?: '');
                }
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body    = $html;
                if ($text) { $mail->AltBody = $text; }

                $mail->send();
                return true;
            } catch (\Throwable $e) {
                $domain = substr($to, strpos($to, '@') + 1);
                error_log('SMTP send failed to ' . $domain . ': ' . $e->getMessage());
                // fall back to native mail()
            }
        }

        // Fallback: native mail()
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        if ($from) {
            $fromHeader = $fromName ? sprintf('"%s" <%s>', $fromName, $from) : $from;
            $headers[] = 'From: ' . $fromHeader;
            $headers[] = 'Reply-To: ' . $fromHeader;
        }
        $ok = mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $html, implode("\r\n", $headers));
        if (!$ok) { 
            $domain = substr($to, strpos($to, '@') + 1);
            error_log('mail() fallback failed to ' . $domain); 
        }
        return $ok;
    }
}
?>