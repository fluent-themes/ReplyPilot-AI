<?php
namespace App\Support;

class Logger {
    protected string $logPath;
    protected $mono; // \Monolog\Logger|null

    public function __construct(string $path)
    {
        $this->logPath = $path;
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $this->mono = null;
        if ($this->bootstrapMonolog()) {
            $this->mono = new \Monolog\Logger('app');
            $this->mono->pushHandler(new \Monolog\Handler\StreamHandler($this->logPath, \Monolog\Logger::DEBUG));
        }
    }

    protected function bootstrapMonolog(): bool
    {
        if (class_exists('Monolog\\Logger') && class_exists('Monolog\\Handler\\StreamHandler')) {
            return true;
        }
        // Try to include Monolog manually if no composer autoload
        $base = __DIR__ . '/../../vendor/monolog/monolog/src/Monolog/';
        $files = [
            'Logger.php',
            'Handler/StreamHandler.php',
            'Level.php',
            'DateTimeImmutable.php'
        ];
        foreach ($files as $f) {
            $p = $base . $f;
            if (file_exists($p)) {
                require_once $p;
            }
        }
        return class_exists('Monolog\\Logger') && class_exists('Monolog\\Handler\\StreamHandler');
    }

    public static function create(): self
    {
        $logPath = __DIR__ . '/../../storage/logs/app.log';
        return new self($logPath);
    }

    protected function write(string $level, string $message, array $context = []): void
    {
        if ($this->mono) {
            $lvl = strtoupper($level);
            $lvlConst = defined('Monolog\\Logger::' . $lvl) ? constant('Monolog\\Logger::' . $lvl) : \Monolog\Logger::INFO;
            $this->mono->log($lvlConst, $message, $context);
            return;
        }
        $date = date('Y-m-d H:i:s');
        $line = "[$date] $level: " . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context);
        }
        $line .= PHP_EOL;
        file_put_contents($this->logPath, $line, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void { $this->write('info', $message, $context); }
    public function error(string $message, array $context = []): void { $this->write('error', $message, $context); }
    public function debug(string $message, array $context = []): void { $this->write('debug', $message, $context); }
}
?>