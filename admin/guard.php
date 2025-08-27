<?php
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Env;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session validity with timeout
$valid = isset($_SESSION['rpai_admin_unlocked']) && $_SESSION['rpai_admin_unlocked'] === true;

// Check session timeout (30 minutes)
if ($valid && isset($_SESSION['rpai_admin_timeout'])) {
    if (time() > $_SESSION['rpai_admin_timeout']) {
        $valid = false;
        session_destroy();
        session_start();
    } else {
        $_SESSION['rpai_admin_timeout'] = time() + 1800; // Reset timeout on activity
    }
}
$token = $_GET['token'] ?? null;
$envPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env';
$expected = null;
if (file_exists($envPath)) {
    $expected = Env::get('INSTALL_TOKEN', '');
} else {
    if (!defined('INSTALL_FALLBACK_TOKEN')) { define('INSTALL_FALLBACK_TOKEN','setup123'); }
    $expected = INSTALL_FALLBACK_TOKEN;
}
if (!$valid) {
    if ($token && $expected !== '' && $token === $expected) {
        session_regenerate_id(true);
        $_SESSION['rpai_admin_unlocked'] = true;
        $_SESSION['rpai_admin_timeout'] = time() + 1800; // 30 minute timeout
        // Redirect to the same page without token in URL
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: $url");
        exit;
    }
    http_response_code(403);
    echo 'Admin access requires a valid token. Visit <code>/?page=install&token=YOUR_INSTALL_TOKEN</code> once (same browser) or append <code>?token=YOUR_INSTALL_TOKEN</code> here one time to unlock.';
    exit;
}
// RPAI_HOOK:guard_passed
