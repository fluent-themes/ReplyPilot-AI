<?php
require __DIR__ . '/../bootstrap.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Repository\SubmissionRepository;
use App\Repository\SubmissionRepositoryMock;
use App\Services\LicenseValidator;
use App\Services\OpenAIHandler;
use App\Services\OpenAIHandlerMock;
use App\Support\Mailer;
use App\Support\MailerMock;

$logger = $GLOBALS['container']['logger'];
$db     = $GLOBALS['container']['db'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim(Request::input('name'));
    $email = trim(Request::input('email'));
    $msg   = trim(Request::input('message'));
    $tone  = trim(Request::input('tone', 'friendly'));
    $purchase = trim(Request::input('purchase_code', ''));
    [$valid, $productName] = LicenseValidator::validate($purchase);
    if (!$valid && $purchase !== '') {
        $error = 'Invalid purchase code';
    }
    if (empty($error)) {
        $prompt = OpenAIHandler::buildSmartPrompt($msg, $tone, $productName ?? 'Your Product');
        $useAiMock = Env::get('OPENAI_API_KEY') === 'MOCK_MODE';
        // PRODUCTION NOTE:
        // To enable real OpenAI: set a real OPENAI_API_KEY in .env (not 'MOCK_MODE')
        $aiService = $useAiMock ? OpenAIHandlerMock::class : OpenAIHandler::class;
        $ai = $aiService::query($prompt);

        $useRepoMock = Env::get('DB_CONNECTION') === 'none';
        // PRODUCTION NOTE:
        // To enable real DB: set DB_CONNECTION and DB_* variables in .env
        $repo = $useRepoMock ? new SubmissionRepositoryMock($db) : new SubmissionRepository($db);
        $repo->save([
            'name' => $name,
            'email' => $email,
            'message' => $msg,
            'tone' => $tone,
            'purchase_code' => $purchase,
            'product_name' => $productName ?? '',
            'category' => $ai['category'],
            'ai_reply' => $ai['reply']
        ]);

        $useMailerMock = Env::get('MAIL_TRANSPORT') === 'file';
        // PRODUCTION NOTE:
        // To send real mail: set MAIL_TRANSPORT=smtp and SMTP_* variables in .env
        $mailer = $useMailerMock ? new MailerMock() : new Mailer();
        $mailer->send($email, 'Your Reply from AI', $ai['reply']);
        header('Location: thank-you.php');
        exit;
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
