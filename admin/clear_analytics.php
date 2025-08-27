<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Route guard: Analytics clearing is disabled
echo json_encode([
    'success' => false,
    'message' => 'Analytics clearing is currently disabled'
]);
exit;
