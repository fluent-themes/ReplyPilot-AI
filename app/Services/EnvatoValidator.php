<?php
namespace App\Services;

use App\Contracts\LicenseValidatorInterface;
use App\Support\Settings;

/**
 * Envato Market license validator
 */
class EnvatoValidator implements LicenseValidatorInterface
{
    public function validate(string $code, array $options = []): array
    {
        $enabled = Settings::get('purchase_validation_enabled', false);
        if (!$enabled || $code === '') {
            return [
                'valid' => false,
                'product_name' => '',
                'error' => null,
                'details' => []
            ];
        }
        
        $token = trim(Settings::getSecure('envato_personal_token', ''));
        if ($token === '') {
            return [
                'valid' => false,
                'product_name' => '',
                'error' => 'No Envato token configured',
                'details' => []
            ];
        }
        
        $base = 'https://api.envato.com';
        $url = $base . '/v3/market/author/sale?code=' . urlencode($code);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: ReplyPilot-AI (license check)',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            error_log('Envato cURL error: ' . $err);
            curl_close($ch);
            return [
                'valid' => false,
                'product_name' => '',
                'error' => 'Network error: ' . $err,
                'details' => []
            ];
        }
        
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http === 404) {
            return [
                'valid' => false,
                'product_name' => '',
                'error' => 'Purchase code not found',
                'details' => []
            ];
        }
        
        if ($http === 401 || $http === 403) {
            error_log('Envato auth error HTTP ' . $http);
            return [
                'valid' => false,
                'product_name' => '',
                'error' => 'Authentication failed - check your Envato token',
                'details' => []
            ];
        }
        
        if ($http !== 200) {
            error_log('Envato HTTP ' . $http . ' response: ' . substr($raw, 0, 1000));
            return [
                'valid' => false,
                'product_name' => '',
                'error' => 'API error (HTTP ' . $http . ')',
                'details' => []
            ];
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['item']['id'])) {
            return [
                'valid' => false,
                'product_name' => '',
                'error' => 'Invalid response format',
                'details' => []
            ];
        }
        
        $itemName = $data['item']['name'] ?? '';
        $itemId = (int)($data['item']['id'] ?? 0);
        
        // Check if item ID is in allowed list
        $allowedIds = Settings::get('envato_allowed_item_ids', '');
        if ($allowedIds !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $allowedIds)), 'strlen');
            if (!in_array((string)$itemId, $allowed, true)) {
                return [
                    'valid' => false,
                    'product_name' => $itemName,
                    'error' => 'This product is not supported (ID: ' . $itemId . ')',
                    'details' => ['item_id' => $itemId, 'allowed_ids' => $allowed]
                ];
            }
        }
        
        // Log successful validation (without sensitive data)
        error_log('Envato validation success: item=' . $itemId . ' name=' . $itemName);
        
        return [
            'valid' => true,
            'product_name' => $itemName,
            'error' => null,
            'details' => [
                'item_id' => $itemId,
                'buyer_username' => $data['buyer'] ?? '',
                'purchase_date' => $data['sold_at'] ?? '',
                'license' => $data['license'] ?? ''
            ]
        ];
    }
    
    public function testConnection(): array
    {
        $token = trim(Settings::getSecure('envato_personal_token', ''));
        if ($token === '') {
            return [
                'connected' => false,
                'message' => 'No Envato token configured',
                'user_info' => []
            ];
        }
        
        $url = 'https://api.envato.com/v1/market/user:username.json';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: ReplyPilot-AI (connection test)',
            ],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        
        $raw = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($raw === false) {
            return [
                'connected' => false,
                'message' => 'Network error',
                'user_info' => []
            ];
        }
        
        if ($http !== 200) {
            return [
                'connected' => false,
                'message' => 'Authentication failed (HTTP ' . $http . ')',
                'user_info' => []
            ];
        }
        
        $data = json_decode($raw, true);
        $username = $data['username'] ?? 'Unknown';
        
        return [
            'connected' => true,
            'message' => 'Connected as: ' . $username,
            'user_info' => $data
        ];
    }
    
    public function getConfigSchema(): array
    {
        return [
            'personal_token' => [
                'type' => 'password',
                'label' => 'Envato Personal Token',
                'required' => true,
                'help' => 'Get your personal token from Envato API settings',
                'secure' => true
            ],
            'allowed_item_ids' => [
                'type' => 'text',
                'label' => 'Allowed Item IDs',
                'help' => 'Comma-separated list of item IDs to validate against (optional)',
                'placeholder' => '12345, 67890'
            ],
            'validation_enabled' => [
                'type' => 'checkbox',
                'label' => 'Enable Purchase Validation',
                'default' => false
            ]
        ];
    }
    
    public function getProviderInfo(): array
    {
        return [
            'name' => 'Envato Market',
            'features' => [
                'purchase_validation',
                'item_verification',
                'buyer_information',
                'license_details'
            ],
            'rate_limits' => [
                'requests_per_minute' => 100,
                'requests_per_hour' => 5000
            ]
        ];
    }
    
    public function getAvailableProducts(): array
    {
        // This would require additional API calls to get user's items
        // For now, return empty array (can be implemented later)
        return [];
    }
    
    public function validateConfig(array $config): array
    {
        $errors = [];
        
        if (empty($config['personal_token'])) {
            $errors[] = 'Personal token is required';
        }
        
        if (!empty($config['allowed_item_ids'])) {
            $ids = array_map('trim', explode(',', $config['allowed_item_ids']));
            foreach ($ids as $id) {
                if (!is_numeric($id)) {
                    $errors[] = "Invalid item ID: {$id}";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
