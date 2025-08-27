<?php
namespace App\Helpers;

/**
 * Single source of truth for production vs mock mode
 */
class ModeHelper {
    private static ?bool $isMock = null;
    private static ?bool $isInstalled = null;
    
    public static function isMock(): bool {
        if (self::$isMock === null) {
            self::$isMock = (isset($_GET['mode']) && $_GET['mode'] === 'mock123');
        }
        return self::$isMock;
    }
    
    public static function isProd(): bool {
        return !self::isMock();
    }
    
    /**
     * Check if the application is installed and configured
     */
    public static function isInstalled(): bool {
        if (self::$isInstalled === null) {
            // Check if .env file exists (basic installation indicator)
            self::$isInstalled = file_exists(__DIR__ . '/../../.env');
        }
        return self::$isInstalled;
    }
    
    /**
     * Determine if we should use mock database
     * Uses mock if explicitly requested OR if not installed
     */
    public static function shouldUseMockDB(): bool {
        return self::isMock() || !self::isInstalled();
    }
}
