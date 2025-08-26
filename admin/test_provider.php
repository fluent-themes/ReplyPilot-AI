<?php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/guard.php';

header('Content-Type: application/json; charset=utf-8');

use App\Factories\AIProviderFactory;
use App\Factories\LicenseValidatorFactory;

try {
    $type = $_GET['type'] ?? '';
    $provider = $_GET['provider'] ?? '';
    
    if (!$type || !$provider) {
        throw new \InvalidArgumentException('Missing type or provider parameter');
    }
    
    if ($type === 'ai') {
        $instance = AIProviderFactory::create($provider);
        $result = $instance->testConnection();
        
        echo json_encode([
            'ok' => true,
            'data' => [
                'success' => $result['available'] ?? false,
                'message' => $result['message'] ?? 'Test completed',
                'provider_info' => $instance->getProviderInfo()
            ]
        ]);
        exit;
        
    } elseif ($type === 'license') {
        $instance = LicenseValidatorFactory::create($provider);
        $result = $instance->testConnection();
        
        echo json_encode([
            'ok' => true,
            'data' => [
                'success' => $result['connected'] ?? false,
                'message' => $result['message'] ?? 'Test completed',
                'user_info' => $result['user_info'] ?? null,
                'provider_info' => $instance->getProviderInfo()
            ]
        ]);
        exit;
        
    } else {
        throw new \InvalidArgumentException('Invalid type parameter');
    }
    
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => [
            'id' => 'provider_test_failed',
            'message' => 'Test failed: ' . $e->getMessage(),
            'hint' => 'Verify provider configuration and API keys'
        ],
        'request_id' => bin2hex(random_bytes(6))
    ]);
    exit;
}
