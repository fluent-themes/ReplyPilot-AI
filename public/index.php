<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/
require __DIR__ . '/../bootstrap.php';

if (isset($_GET['page'])) {
    if ($_GET['page'] === 'install') {
        require __DIR__ . '/installer.php';
        exit;
    }
    if ($_GET['page'] === 'ticket') {
        require __DIR__ . '/ticket.php';
        exit;
    }
}

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
use App\Support\Settings;
use App\Helpers\ModeHelper;
use App\Helpers\TicketHelper;

$logger = $GLOBALS['container']['logger'];
$db     = $GLOBALS['container']['db_factory'](); // Get DB connection from factory

// Check if database is available
if (!$db) {
    // Graceful degradation - show installation required message
    echo '<!doctype html><html><head><title>Installation Required</title></head><body>';
    echo '<h1>Installation Required</h1>';
    echo '<p>ReplyPilot AI needs to be installed and configured.</p>';
    echo '<p><a href="?page=install&token=setup123">Click here to install</a></p>';
    echo '</body></html>';
    exit;
}

// Get form configuration from settings
$purchaseCodeEnabled = Settings::get('purchase_code_enabled', false);
$purchaseCodeRequired = Settings::get('purchase_code_required', false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim(Request::input('name'));
    $email = trim(Request::input('email'));
    $msg   = trim(Request::input('message'));
    $tone  = trim(Request::input('tone', 'friendly'));
    $purchase = trim(Request::input('purchase_code', ''));
    
    // Validate purchase code if needed
    $productName = '';
    $error = '';
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address';
    }
    
    // Check if purchase code is required
    if (empty($error) && $purchaseCodeRequired && $purchase === '') {
        $error = 'Purchase code is required';
    } elseif (empty($error) && $purchase !== '') {
        [$valid, $productName, $validationError] = LicenseValidator::validate($purchase);
        if (!$valid) {
            $error = $validationError ?: 'Invalid purchase code';
        }
    }
    
    if (empty($error)) {
        $prompt = OpenAIHandler::buildSmartPrompt($msg, $tone, $productName ?: 'Your Product');
        
        // Use unified mode helper for AI service selection
        $aiService = ModeHelper::isMock() ? OpenAIHandlerMock::class : OpenAIHandler::class;
        $ai = $aiService::query($prompt);

        // Use unified mode helper for repository selection
        $repo = ModeHelper::isMock() ? new SubmissionRepositoryMock($db) : new SubmissionRepository($db);
        $ref = $repo->save([
            'name' => $name,
            'email' => $email,
            'message' => $msg,
            'tone' => $tone,
            'purchase_code' => $purchase,
            'product_name' => $productName,
            'category' => $ai['category'],
            'ai_reply' => $ai['reply']
        ]);
        
        // Allow this session to access the created ticket
        TicketHelper::allowAccess($ref);

        // Use unified mode helper for mailer selection
        $mailer = ModeHelper::isMock() ? new MailerMock() : new Mailer();
        $mailer->send($email, 'Your Reply from AI', $ai['reply']);
        
        // Redirect to thank you page with ticket reference
        header('Location: thank-you.php?ref=' . urlencode($ref));
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact Support — ReplyPilot</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/main.js" defer></script>
</head>
<body>
  <main class="container">
    <div class="card" style="max-width: 760px; margin: 0 auto;">
      <div class="card-body">
        <h1 style="margin:0 0 8px">Contact Support</h1>
        <p style="margin:0 0 18px; color:var(--muted)">We typically respond within 1–2 business days.</p>

        <form method="post" action="">
          <?php if(!empty($error)): ?>
            <div class="note danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <div class="form-row">
            <div>
              <label for="f-name">Name</label>
              <input id="f-name" name="name" required>
            </div>
            <div>
              <label for="f-email">Email</label>
              <input id="f-email" type="email" name="email" required>
            </div>
          </div>

          <label for="f-message">Message</label>
          <textarea id="f-message" name="message" required rows="7"></textarea>

          <div class="form-row">
            <div>
              <label for="f-product">Product Name</label>
              <input id="f-product" name="product_name">
            </div>
            <div>
              <label for="f-tone">Tone</label>
              <select id="f-tone" name="tone">
                <option value="friendly">Friendly</option>
                <option value="professional">Professional</option>
              </select>
            </div>
          </div>

          <?php if ($purchaseCodeEnabled): ?>
          <label for="f-purchase">Purchase Code <?= $purchaseCodeRequired ? '(required)' : '(optional)' ?></label>
          <input id="f-purchase" name="purchase_code" <?= $purchaseCodeRequired ? 'required' : '' ?>>
          <?php endif; ?>

          <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap">
            <button class="btn primary" type="submit">Send</button>
            <button class="btn ghost" type="reset">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>
