
<?php
require __DIR__.'/../bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Services\OpenAIHandler;
use App\Services\LicenseValidator;
use App\Repository\SubmissionRepository;
use App\Support\Mailer;

$logger = $GLOBALS['container']['logger'];
$db     = $GLOBALS['container']['db'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim(Request::input('name'));
    $email = trim(Request::input('email'));
    $msg   = trim(Request::input('message'));
    $tone  = trim(Request::input('tone','friendly'));
    $purchase = trim(Request::input('purchase_code',''));
    [$valid,$productName] = LicenseValidator::validate($purchase);
    if(!$valid && $purchase!==''){
        $error = 'Invalid purchase code';
    }
    if(empty($error)){
        $prompt = OpenAIHandler::buildSmartPrompt($msg,$tone,$productName??'Your Product');
        $ai = OpenAIHandler::query($prompt);
        $repo = new SubmissionRepository($db);
        $repo->save([
            'name'=>$name,
            'email'=>$email,
            'message'=>$msg,
            'tone'=>$tone,
            'purchase_code'=>$purchase,
            'product_name'=>$productName??'',
            'category'=>$ai['category'],
            'ai_reply'=>$ai['reply']
        ]);
        $mailer = new Mailer();
        $mailer->send($email,'Your Reply from AI',$ai['reply']);
        header('Location: thank-you.php');exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Contact Support</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/main.js" defer></script>
</head>
<body>
<form method="post">
  <h1>Contact Support</h1>
  <?php if(!empty($error)): ?>
    <div class="alert alert-error"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>
  <label>Name
    <input name="name" required>
  </label>
  <label>Email
    <input name="email" type="email" required>
  </label>
  <label>Message
    <textarea name="message" rows="5" required></textarea>
  </label>
  <label>Tone
    <select name="tone">
      <option value="friendly">Friendly</option>
      <option value="professional">Professional</option>
    </select>
  </label>
  <label>Purchase Code (optional)
    <input name="purchase_code">
  </label>
  <button>Send</button>
</form>
</body>
</html>
