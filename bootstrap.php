<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
*/

// Safe Composer autoload guard for shared hosting
if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
} else {
    // Fallback PSR-4 autoloader for App\ namespace when vendor missing
    if (is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'app')) {
        spl_autoload_register(function($class) {
            if (strpos($class, 'App\\') === 0) {
                $file = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4)) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });
    }
}

// Bootstrap PHP logging to storage/logs/app.log
$logDir = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
error_reporting(E_ALL);
ini_set('log_errors','1');
ini_set('error_log', $logDir . DIRECTORY_SEPARATOR . 'app.log');

// Load env and define request-scoped mock mode

use App\Core\Env;
use App\Support\Database;
use App\Support\DatabaseMock;
use App\Support\Logger;
use App\Helpers\ModeHelper;

Env::load(__DIR__ . DIRECTORY_SEPARATOR . '.env');

$logger = Logger::create();
$logger->info('request', ['mode' => (ModeHelper::isMock() ? 'mock' : 'prod'), 'installed' => ModeHelper::isInstalled()]);

// Lazy database factory - only connects when called
$dbFactory = function() {
    if (ModeHelper::shouldUseMockDB()) {
        return DatabaseMock::create();
    } else {
        return Database::createSafe();
    }
};

$GLOBALS['container'] = [
    'logger' => $logger,
    'db_factory' => $dbFactory,
];
