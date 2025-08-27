<?php namespace App\Services;
use App\Support\Settings;

class LicenseValidator {
    /**
     * Validate an Envato Market purchase code using the Author Sales API.
     * Returns [isValid(bool), productName(string), error(string|null)]
     */
    public static function validate(string $code): array {
        $enabled = Settings::get('purchase_validation_enabled', false);
        if (!$enabled || $code === '') {
            return [false, '', null];
        }
        
        $token = trim(Settings::getSecure('envato_personal_token', ''));
        if ($token === '') {
            return [false, '', 'No Envato token configured'];
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
            return [false, '', 'Network error: ' . $err];
        }
        
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http === 404) {
            return [false, '', 'Purchase code not found'];
        }
        if ($http === 401 || $http === 403) {
            error_log('Envato auth error HTTP ' . $http);
            return [false, '', 'Authentication failed - check your Envato token'];
        }
        if ($http !== 200) {
            error_log('Envato HTTP ' . $http . ' response: ' . substr($raw, 0, 1000));
            return [false, '', 'API error (HTTP ' . $http . ')'];
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['item']['id'])) {
            return [false, '', 'Invalid response format'];
        }
        
        $itemName = $data['item']['name'] ?? '';
        $itemId = (int)($data['item']['id'] ?? 0);
        
        // Check if item ID is in allowed list
        $allowedIds = Settings::get('envato_allowed_item_ids', '');
        if ($allowedIds !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $allowedIds)), 'strlen');
            if (!in_array((string)$itemId, $allowed, true)) {
                return [false, $itemName, 'This product is not supported (ID: ' . $itemId . ')'];
            }
        }
        
        // Log successful validation (without sensitive data)
        error_log('Envato validation success: item=' . $itemId . ' name=' . $itemName);
        
        return [true, $itemName, null];
    }
    
    /**
     * Test the Envato API connection
     */
    public static function testConnection(): array {
        $token = trim(Settings::getSecure('envato_personal_token', ''));
        if ($token === '') {
            return [false, 'No Envato token configured'];
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
            return [false, 'Network error'];
        }
        
        if ($http !== 200) {
            return [false, 'Authentication failed (HTTP ' . $http . ')'];
        }
        
        $data = json_decode($raw, true);
        $username = $data['username'] ?? 'Unknown';
        
        return [true, 'Connected as: ' . $username];
    }
}
?>