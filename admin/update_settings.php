<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';
use App\Support\Settings;

try {
    
    // CSRF Protection
    $providedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    
    if (!$providedToken || !$sessionToken || !hash_equals($sessionToken, $providedToken)) {
        // Invalidate token on failure
        unset($_SESSION['csrf_token']);
        header('Location: ./?status=csrf_error');
        exit;
    }
    
    // POST handling with CSRF protection
    $pv  = isset($_POST['purchase_validation_enabled']);
    $pce = isset($_POST['purchase_code_enabled']);
    $pcr = isset($_POST['purchase_code_required']);

    Settings::set('purchase_validation_enabled', $pv);
    Settings::set('purchase_code_enabled', $pce);
    Settings::set('purchase_code_required', $pcr);

    header('Location: ./');
    exit;

} catch (\Throwable $e) {
    error_log('Update settings error: ' . $e->getMessage());
    header('Location: ./?status=error');
    exit;
}
