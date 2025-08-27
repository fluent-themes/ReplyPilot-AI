<?php
require __DIR__ . '/guard.php';

use App\Support\Mailer;
use App\Repository\EmailRepository;

try {
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
    
    // Rate limiting - max 10 emails per minute
    $currentTime = time();
    if (!isset($_SESSION['email_rate_limit'])) {
        $_SESSION['email_rate_limit'] = ['count' => 0, 'reset_time' => $currentTime + 60];
    }
    
    if ($currentTime > $_SESSION['email_rate_limit']['reset_time']) {
        $_SESSION['email_rate_limit'] = ['count' => 0, 'reset_time' => $currentTime + 60];
    }
    
    if ($_SESSION['email_rate_limit']['count'] >= 10) {
        header('Location: ./?status=rate_limit');
        exit;
    }
    
    $_SESSION['email_rate_limit']['count']++;

    $idValue = $_POST['id'] ?? 0;
    if ($idValue && !is_numeric($idValue)) {
        header('Location: ./?status=invalid_id');
        exit;
    }
    $id = (int)$idValue;
    $to      = trim($_POST['to'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    // Validate email and sanitize subject to prevent header injection
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        header('Location: ./?status=invalid_email');
        exit;
    }

    // Sanitize subject to prevent header injection
    $subject = str_replace(["\r", "\n"], '', $subject);

    if (!$subject || !$body) {
        header('Location: ./?status=invalid');
        exit;
    }

    $mailer = new Mailer();
    $ok = $mailer->send($to, $subject, $body);

    // Log email
    $db = $GLOBALS['container']['db_factory'](); // Get DB connection from factory
    if ($db) {
        try {
            $repo = new EmailRepository($db);
            $repo->logOutbound($id, $to, $subject, $body, $ok ? 'sent' : 'failed', null, $ok ? null : 'send_failed');
        } catch (\Throwable $e) {
            // Log the logging error but don't fail the main operation
            error_log('Email logging failed: ' . $e->getMessage());
        }
    }

    $status = urlencode($ok ? 'sent' : 'failed');
    $anchor = $id ? '#' . urlencode("row-$id") : '';
    header('Location: ./?status=' . $status . $anchor);
    exit;

} catch (\Throwable $e) {
    error_log('Send email error: ' . $e->getMessage());
    header('Location: ./?status=error');
    exit;
}
