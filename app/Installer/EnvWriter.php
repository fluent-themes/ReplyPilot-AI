<?php namespace App\Installer;
class EnvWriter {
    public static function write(array $vars, $path){
        // Check if parent directory is writable
        $parentDir = dirname($path);
        if (!is_writable($parentDir)) {
            throw new \RuntimeException("Parent directory is not writable: $parentDir");
        }
        
        $content = '';
        foreach($vars as $k=>$v){
            $content .= $k.'='.$v.PHP_EOL;
        }
        
        // Atomic write: use temp file then rename
        $tempPath = $path . '.tmp.' . uniqid('', true) . '.' . bin2hex(random_bytes(4));
        $handle = fopen($tempPath, 'w');
        if (!$handle) {
            throw new \RuntimeException("Cannot create temporary file for .env writing: $tempPath");
        }
        
        if (fwrite($handle, $content) === false) {
            fclose($handle);
            @unlink($tempPath);
            throw new \RuntimeException("Failed to write .env content to temporary file");
        }
        
        if (!fflush($handle) || !fclose($handle)) {
            @unlink($tempPath);
            throw new \RuntimeException("Failed to flush/close .env temporary file");
        }
        
        if (!rename($tempPath, $path)) {
            @unlink($tempPath);
            throw new \RuntimeException("Failed to move temporary .env file to final location");
        }
    }

    public static function update(array $vars, string $path): void {
        $lines = [];
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
        }
        $map = [];
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$k,$v] = explode('=', $line, 2);
                $map[trim($k)] = $v;
            }
        }
        foreach ($vars as $k=>$v) {
            $map[$k] = $v;
        }
        $out = '';
        foreach ($map as $k=>$v) {
            $out .= $k.'='.$v.PHP_EOL;
        }
        
        // Atomic write: use temp file then rename
        $tempPath = $path . '.tmp.' . uniqid('', true) . '.' . bin2hex(random_bytes(4));
        $handle = fopen($tempPath, 'w');
        if (!$handle) {
            throw new \RuntimeException("Cannot create temporary file for .env update: $tempPath");
        }
        
        if (fwrite($handle, $out) === false) {
            fclose($handle);
            @unlink($tempPath);
            throw new \RuntimeException("Failed to write .env content to temporary file during update");
        }
        
        if (!fflush($handle) || !fclose($handle)) {
            @unlink($tempPath);
            throw new \RuntimeException("Failed to flush/close .env temporary file during update");
        }
        
        if (!rename($tempPath, $path)) {
            @unlink($tempPath);
            throw new \RuntimeException("Failed to move temporary .env file to final location during update");
        }
    }
}
?>