<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

// PLACEHOLDER - ANALYTICS EXPORT DISABLED
// Original functionality moved to extraFeatures/

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? 'full_report';
$days = (int) ($_GET['days'] ?? 30);

// Return error response indicating feature is disabled
echo json_encode([
    'ok' => false,
    'error' => [
        'message' => 'Analytics export is currently disabled'
    ],
    'type' => $type,
    'days' => $days,
    'timestamp' => date('Y-m-d H:i:s')
]);
exit;
