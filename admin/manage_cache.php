<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

// PLACEHOLDER - RESPONSE CACHING DISABLED
// Original functionality moved to extraFeatures/

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => ['message' => 'Method not allowed']]);
    exit;
}

$action = $_GET['action'] ?? '';

// All cache management actions return "disabled" response
switch ($action) {
    case 'optimize':
        echo json_encode([
            'ok' => false,
            'error' => ['message' => 'Response caching is currently disabled']
        ]);
        exit;
        
    case 'clear':
        echo json_encode([
            'ok' => true,
            'message' => 'No cache to clear (feature disabled)'
        ]);
        exit;
        
    case 'clean':
        echo json_encode([
            'ok' => true,
            'message' => 'No expired entries to clean (feature disabled)'
        ]);
        exit;
        
    default:
        echo json_encode([
            'ok' => false,
            'error' => ['message' => 'Unknown action']
        ]);
        exit;
}
