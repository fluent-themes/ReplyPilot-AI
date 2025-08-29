<?php
require __DIR__ . '/guard.php';

use App\Support\Mailer;
use App\Core\Env;
use App\Repository\EmailRepository;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(400);
    exit('Invalid CSRF token');
}

$idValue = $_POST['id'] ?? 0;
if (!is_numeric($idValue)) {
    header('Location: ./?status=invalid_id');
    exit;
}
$id = (int)$idValue;
$ai_reply  = trim($_POST['ai_reply'] ?? '');
$category  = trim($_POST['category'] ?? '');
$send      = isset($_POST['send']) && $_POST['send'] === '1';
$to        = trim($_POST['to'] ?? '');
$subject   = trim($_POST['subject'] ?? '');
$body      = trim($_POST['body'] ?? '');

$db = $GLOBALS['container']['db'];

// Update reply and category
$stmt = $db->prepare("UPDATE submissions SET ai_reply = ?, category = ? WHERE id = ?");
$stmt->execute([$ai_reply, $category, $id]);

$status = 'updated';

if ($send && $to && $subject && $body) {
    // Validate email and sanitize subject to prevent header injection
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $anchor = urlencode("row-$id");
        header("Location: ./?status=invalid_email#$anchor");
        exit;
    }
    
    // Check email length (RFC 5321 specifies max 254 chars)
    if (strlen($to) > 254) {
        $anchor = urlencode("row-$id");
        header("Location: ./?status=email_too_long#$anchor");
        exit;
    }
    
    // Sanitize subject to prevent header injection
    $subject = str_replace(["\r", "\n"], '', $subject);
    
    $mailer = new Mailer();
    $ok = $mailer->send($to, $subject, $body);
    // Log email
    if ($db) {
        try {
            $repo = new EmailRepository($db);
            $repo->logOutbound($id, $to, $subject, $body, $ok ? 'sent' : 'failed', null, $ok ? null : 'send_failed');
        } catch (\Throwable $e) {}
    }
    $status = $ok ? 'updated_sent' : 'updated_send_failed';
}

$anchor = urlencode("row-$id");
header("Location: ./?status={$status}#$anchor");
exit;
