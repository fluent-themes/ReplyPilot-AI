<?php
namespace App\Core;

class Request {
    public static function input($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}
?>
