
<?php namespace PHPMailer\PHPMailer;
class PHPMailer {
    public $isSMTP = false;
    public $Host;
    public $SMTPAuth = true;
    public $Username;
    public $Password;
    public $SMTPSecure;
    public $Port;
    public $From;
    public $FromName;
    public $Subject;
    public $Body;
    public $AltBody;
    private $to = [];
    public function isSMTP() { $this->isSMTP = true; }
    public function addAddress($email, $name='') { $this->to[] = [$email,$name]; }
    public function send() { return true; }
}
?>
