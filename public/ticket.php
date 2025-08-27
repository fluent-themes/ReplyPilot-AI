<?php
require __DIR__ . '/../bootstrap.php';

use App\Helpers\TicketHelper;
use App\Repository\SubmissionRepository;
use App\Repository\SubmissionRepositoryMock;
use App\Helpers\ModeHelper;

$ref = trim($_GET['ref'] ?? '');
if ($ref === '') {
    http_response_code(400);
    echo 'Missing ticket reference';
    exit;
}

if (!TicketHelper::canAccess($ref)) {
    http_response_code(403);
    echo 'Access denied. You can only view tickets you created in this session.';
    exit;
}

$db = $GLOBALS['container']['db'];
$repo = ModeHelper::isMock() ? new SubmissionRepositoryMock($db) : new SubmissionRepository($db);

try {
    $ticket = $repo->findByRef($ref);
    if (!$ticket) {
        http_response_code(404);
        echo 'Ticket not found';
        exit;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Server error loading ticket';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Support Ticket #<?= htmlspecialchars($ref) ?> — ReplyPilot</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="container">
    <div class="card" style="max-width: 760px; margin: 0 auto;">
      <div class="card-body">
        <h1 style="margin:0 0 8px">Support Ticket #<?= htmlspecialchars($ref) ?></h1>
        <p style="margin:0 0 18px; color:var(--muted)">Submitted: <?= htmlspecialchars($ticket['created_at'] ?? 'Unknown') ?></p>

        <div style="margin-bottom: 20px;">
          <h3>Your Message:</h3>
          <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
            <strong>From:</strong> <?= htmlspecialchars($ticket['name']) ?> &lt;<?= htmlspecialchars($ticket['email']) ?>&gt;<br>
            <strong>Product:</strong> <?= htmlspecialchars($ticket['product_name'] ?: 'N/A') ?><br>
            <strong>Category:</strong> <?= htmlspecialchars($ticket['category'] ?: 'General') ?><br><br>
            <?= nl2br(htmlspecialchars($ticket['message'])) ?>
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <h3>AI Reply:</h3>
          <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
            <?= nl2br(htmlspecialchars($ticket['ai_reply'])) ?>
          </div>
        </div>

        <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap">
          <a class="btn primary" href="index.php">Submit Another Request</a>
          <a class="btn ghost" href="mailto:<?= htmlspecialchars($ticket['email']) ?>">Contact by Email</a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
