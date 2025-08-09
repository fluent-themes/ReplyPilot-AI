<?php
namespace App\Support;

use App\Core\Env;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer {
    protected PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->From = Env::get('MAIL_FROM_ADDRESS');
        $this->mailer->FromName = Env::get('MAIL_FROM_NAME');
    }

    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        $m = $this->mailer;
        $m->addAddress($to);
        $m->Subject = $subject;
        $m->Body = $html;
        $m->AltBody = $text ?: strip_tags($html);
        return $m->send();
    }
}
