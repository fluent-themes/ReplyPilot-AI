<?php
namespace App\Core;

use Dotenv\Dotenv;

class Env {
    protected static bool $loaded = false;
    protected static array $defaults = [
        'APP_ENV' => 'local',
        'APP_DEBUG' => 'true',
        'LOG_CHANNEL' => 'single',
        'OPENAI_API_KEY' => 'MOCK_MODE',
        'OPENAI_MODEL' => 'gpt-4o-mini',
        'MAIL_TRANSPORT' => 'file',
        'MAIL_FROM_ADDRESS' => 'noreply@example.test',
        'MAIL_FROM_NAME' => 'AI Reply Bot',
        'DB_CONNECTION' => 'none',
    ];

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        if (is_file($path)) {
            Dotenv::createImmutable(dirname($path))->safeLoad();
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
