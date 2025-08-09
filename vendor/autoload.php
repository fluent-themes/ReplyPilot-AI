
<?php
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Try vendor stubs
        $path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($path)) {
            require $path;
        }
        return;
    }
    $relative = substr($class, $len);
    $file = $baseDir . 'app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
?>
