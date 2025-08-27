<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

use App\Support\Analytics;
use App\Support\ResponseCache;
use App\Support\Database;

header('Content-Type: application/json');

try {
    $analytics = new Analytics();
    $cache = new ResponseCache();
    
    // Test analytics tables exist
    $db = new Database();
    $tables = ['ai_analytics', 'license_analytics', 'performance_analytics', 'response_cache'];
    $missing = [];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE ?", [$table]);
        if (empty($result)) {
            $missing[] = $table;
        }
    }
    
    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing tables: ' . implode(', ', $missing) . '. Run migration script.',
            'missing_tables' => $missing
        ]);
        exit;
    }
    
    // Test analytics recording
    $testData = [
        'provider' => 'test',
        'model' => 'test-model',
        'message_length' => 100,
        'response_length' => 200,
        'tokens_used' => 50,
        'response_time' => 1.5,
        'category' => 'Support',
        'confidence' => 0.85,
        'cached' => false,
        'tone' => 'friendly',
        'product_name' => 'Test Product',
        'success' => true
    ];
    
    $analytics->recordAIQuery($testData);
    
    // Test cache functionality
    $cache->set('test message', 'friendly', 'Test Product', [
        'reply' => 'Test response',
        'category' => 'Support',
        'confidence' => 0.9,
        'tokens_used' => 25
    ]);
    
    $cached = $cache->get('test message', 'friendly', 'Test Product');
    
    // Get quick stats
    $stats = $analytics->getAIUsageStats(1);
    $cacheStats = $cache->getStats();
    
    echo json_encode([
        'success' => true,
        'message' => 'Analytics system working correctly',
        'test_results' => [
            'tables_exist' => true,
            'analytics_recording' => true,
            'cache_working' => $cached !== null,
            'recent_queries' => $stats['total_queries'],
            'cache_entries' => $cacheStats['total_entries']
        ]
    ]);
    
} catch (\Throwable $e) {
    error_log('Analytics test error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Analytics test failed: ' . $e->getMessage()
    ]);
}
