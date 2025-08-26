
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Thank You</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div style="max-width:480px;margin:60px auto;text-align:center;">
  <h1>Thank you!</h1>
  <p>Your message has been received. We'll get back to you soon.</p>
<?php
  $ref = isset($_GET['ref']) ? htmlspecialchars($_GET['ref'], ENT_QUOTES, 'UTF-8') : '';
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $ticketUrl = $scheme . '://' . $host . '/?page=ticket&ref=' . rawurlencode($ref);
?>
<?php if ($ref): ?>
  <div style="margin: 20px 0;">
    <p><strong>Ticket #<?= htmlspecialchars($ref) ?></strong> has been created.</p>
    <a class="btn primary" href="<?= htmlspecialchars($ticketUrl) ?>">View my Ticket</a>
  </div>
<?php endif; ?>
<p style="margin-top:16px"><a class="btn ghost" href="index.php">Submit Another Request</a></p>
</div>
</body>
</html>
