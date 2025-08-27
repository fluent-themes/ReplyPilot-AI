<?php
namespace App\Core;

use Dotenv\Dotenv;

class Env {
    protected static bool $loaded = false;
    protected static array $defaults = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'LOG_CHANNEL' => 'single',
        'INSTALL_TOKEN' => 'setup123',
        'OPENAI_API_KEY' => '',
        'OPENAI_MODEL' => 'gpt-5-nano',
        'MAIL_TRANSPORT' => 'smtp',
        'MAIL_FROM_ADDRESS' => 'noreply@example.com',
        'MAIL_FROM_NAME' => 'ReplyPilot AI',
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_NAME' => 'replypilot',
        'DB_USER' => 'replypilot',
        'DB_PASS' => '',
    ];

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        if (is_file($path)) {
            if (class_exists('Dotenv\Dotenv')) {
                Dotenv::createImmutable(dirname($path))->safeLoad();
            }
        }
        foreach (self::$defaults as $key => $value) {
            if (getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
}
