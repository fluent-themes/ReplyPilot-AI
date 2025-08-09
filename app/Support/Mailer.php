
<?php namespace App\Support;
use App\Core\Env;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer {
    protected $mailer;
    public function __construct(){
        $this->mailer = new PHPMailer(true);
        $this->mailer->From = 'no-reply@example.com';
        $this->mailer->FromName = 'AI Responder';
    }
    public function send($to, $subject, $html, $text=''){
        $m = $this->mailer;
        $m->addAddress($to);
        $m->Subject = $subject;
        $m->Body = $html;
        $m->AltBody = $text ?: strip_tags($html);
        return $m->send();
    }
}
?>
