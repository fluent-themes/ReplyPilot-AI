
<?php namespace App\Core;

use Dotenv\Dotenv;

class Env {
    protected static $loaded = false;
    public static function load($basePath){
        if(self::$loaded) return;
        if(file_exists($basePath)){
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname($basePath));
            $dotenv->load();
        }
        self::$loaded = true;
    }
    public static function get($key, $default=null){
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
?>
