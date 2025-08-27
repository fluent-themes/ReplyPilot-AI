<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

use App\Support\Settings;

try {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: advanced_settings.php');
        exit;
    }
    
    // CSRF Protection
    $providedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    
    if (!$providedToken || !$sessionToken || !hash_equals($sessionToken, $providedToken)) {
        // Invalidate token on failure
        unset($_SESSION['csrf_token']);
        header('Location: advanced_settings.php?error=csrf_error');
        exit;
    }

    // Save AI provider settings
    if (isset($_POST['ai_provider'])) {
        Settings::set('ai_provider', $_POST['ai_provider']);
    }

    // Save license validator settings
    if (isset($_POST['license_validator'])) {
        Settings::set('license_validator', $_POST['license_validator']);
    }

    // Save purchase validation settings
    Settings::set('purchase_validation_enabled', isset($_POST['purchase_validation_enabled']));
    Settings::set('purchase_code_enabled', isset($_POST['purchase_code_enabled']));
    Settings::set('purchase_code_required', isset($_POST['purchase_code_required']));

    // Save AI provider specific settings
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ai_') === 0 && $key !== 'ai_provider') {
            // Extract provider and setting name
            $parts = explode('_', $key, 3);
            if (count($parts) === 3) {
                $provider = $parts[1];
                $setting = $parts[2];
                
                // For secure settings (passwords), use encrypted storage
                if (in_array($setting, ['api_key', 'token', 'secret'])) {
                    Settings::setSecure("{$provider}_{$setting}", $value);
                } else {
                    Settings::set("{$provider}_{$setting}", $value);
                }
            }
        }
    }

    // Save license provider specific settings
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'license_') === 0 && $key !== 'license_validator') {
            // Extract provider and setting name
            $parts = explode('_', $key, 3);
            if (count($parts) === 3) {
                $provider = $parts[1];
                $setting = $parts[2];
                
                // For secure settings, use encrypted storage
                if (in_array($setting, ['personal_token', 'api_key', 'secret'])) {
                    Settings::setSecure("{$provider}_{$setting}", $value);
                } else {
                    Settings::set("{$provider}_{$setting}", $value);
                }
            }
        }
    }

    // Save email settings
    $emailFields = ['mail_transport', 'mail_from_name', 'mail_from_address'];
    foreach ($emailFields as $field) {
        if (isset($_POST[$field])) {
            Settings::set($field, $_POST[$field]);
        }
    }

    // Save security settings
    $securityFields = ['ajax_rate_limit', 'session_timeout', 'ai_token_limit'];
    foreach ($securityFields as $field) {
        if (isset($_POST[$field])) {
            Settings::set($field, (int)$_POST[$field]);
        }
    }

    // Save analytics settings
    $analyticsCheckboxes = [
        'analytics_enabled', 'performance_analytics_enabled', 
        'license_analytics_enabled', 'error_alerting_enabled'
    ];
    foreach ($analyticsCheckboxes as $field) {
        Settings::set($field, isset($_POST[$field]));
    }

    $analyticsFields = [
        'analytics_retention_days', 'dashboard_refresh_interval', 'error_threshold_percent'
    ];
    foreach ($analyticsFields as $field) {
        if (isset($_POST[$field])) {
            Settings::set($field, (int)$_POST[$field]);
        }
    }

    // Save cache settings
    $cacheCheckboxes = [
        'response_cache_enabled', 'cache_auto_cleanup'
    ];
    foreach ($cacheCheckboxes as $field) {
        Settings::set($field, isset($_POST[$field]));
    }

    $cacheFields = [
        'cache_ttl', 'cache_max_entries', 'cache_cleanup_frequency'
    ];
    foreach ($cacheFields as $field) {
        if (isset($_POST[$field])) {
            Settings::set($field, (int)$_POST[$field]);
        }
    }

    if (isset($_POST['cache_similarity_threshold'])) {
        Settings::set('cache_similarity_threshold', (float)$_POST['cache_similarity_threshold']);
    }

    // Save prompt optimization settings
    $promptCheckboxes = [
        'prompt_optimization_enabled', 'prompt_optimization_analytics', 'prompt_token_optimization'
    ];
    foreach ($promptCheckboxes as $field) {
        Settings::set($field, isset($_POST[$field]));
    }

    $promptFields = [
        'prompt_optimization_level', 'prompt_template_support', 'prompt_template_sales'
    ];
    foreach ($promptFields as $field) {
        if (isset($_POST[$field])) {
            Settings::set($field, $_POST[$field]);
        }
    }

    header('Location: advanced_settings.php?saved=1');
    exit;

} catch (\Throwable $e) {
    error_log('Update advanced settings error: ' . $e->getMessage());
    header('Location: advanced_settings.php?error=1');
    exit;
}
