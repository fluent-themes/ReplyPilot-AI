<?php
namespace App\Support;

use App\Core\Env;

/**
 * Enhanced Settings with encrypted storage for sensitive data
 */
class Settings {
    protected static $cache = null;
    protected static $secureCache = null;
    
    protected static function path(): string {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app_settings.json';
    }
    
    protected static function securePath(): string {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'secure_settings.json';
    }
    
    protected static function getEncryptionKey(): string {
        $key = Env::get('APP_KEY', '');
        if ($key === '') {
            // Generate and store a key if none exists
            $key = base64_encode(random_bytes(32));
            // In production, this should be set in .env manually
            error_log('Generated APP_KEY: ' . $key . ' - Add this to your .env file');
        }
        return hash('sha256', $key, true);
    }
    
    protected static function encrypt(string $data): string {
        $key = self::getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    protected static function decrypt(string $data): string {
        $key = self::getEncryptionKey();
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    protected static function ensureLoaded(): void {
        if (self::$cache !== null) return;
        
        $p = self::path();
        if (!is_file($p)) {
            // Seed from .env (one-time), then ignore .env thereafter
            $seed = [
                'purchase_validation_enabled' => self::toBool(Env::get('PURCHASE_VALIDATION_ENABLED', '0')),
                'purchase_code_enabled'       => self::toBool(Env::get('PURCHASE_CODE_ENABLED', '0')),
                'purchase_code_required'      => self::toBool(Env::get('PURCHASE_CODE_REQUIRED', '0')),
                'ai_categorization_enabled'   => true,
                'ai_categorization_confidence_threshold' => 0.8,
            ];
            self::$cache = $seed;
            self::save();
            error_log('Settings seeded from .env (purchase flags)');
        } else {
            $json = @file_get_contents($p);
            $arr = json_decode($json, true);
            self::$cache = is_array($arr) ? $arr : [];
        }
    }
    
    protected static function ensureSecureLoaded(): void {
        if (self::$secureCache !== null) return;
        
        $p = self::securePath();
        if (!is_file($p)) {
            self::$secureCache = [];
            self::saveSecure();
        } else {
            $json = @file_get_contents($p);
            $arr = json_decode($json, true);
            self::$secureCache = is_array($arr) ? $arr : [];
        }
    }
    
    protected static function save(): void {
        $p = self::path();
        $dir = dirname($p);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        @file_put_contents($p, json_encode(self::$cache, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
    
    protected static function saveSecure(): void {
        $p = self::securePath();
        $dir = dirname($p);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        @file_put_contents($p, json_encode(self::$secureCache, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
    
    protected static function toBool($v): bool {
        $s = strtolower((string)$v);
        return in_array($s, ['1','true','yes','on'], true);
    }
    
    public static function get(string $key, $default = null) {
        self::ensureLoaded();
        return array_key_exists($key, self::$cache) ? self::$cache[$key] : $default;
    }
    
    public static function set(string $key, $value): void {
        self::ensureLoaded();
        self::$cache[$key] = $value;
        self::save();
    }
    
    /**
     * Store sensitive data encrypted
     */
    public static function setSecure(string $key, string $value): void {
        self::ensureSecureLoaded();
        self::$secureCache[$key] = self::encrypt($value);
        self::saveSecure();
    }
    
    /**
     * Retrieve and decrypt sensitive data
     */
    public static function getSecure(string $key, string $default = ''): string {
        self::ensureSecureLoaded();
        if (!array_key_exists($key, self::$secureCache)) {
            return $default;
        }
        try {
            return self::decrypt(self::$secureCache[$key]);
        } catch (\Throwable $e) {
            error_log('Settings decryption error for key ' . $key . ': ' . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Remove sensitive data
     */
    public static function removeSecure(string $key): void {
        self::ensureSecureLoaded();
        unset(self::$secureCache[$key]);
        self::saveSecure();
    }
}
