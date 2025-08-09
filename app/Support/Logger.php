<?php
namespace App\Support;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;

class Logger {
    public static function create(): MonoLogger
    {
        $logPath = __DIR__ . '/../../storage/logs/app.log';
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0775, true);
        }
        $logger = new MonoLogger('app');
        $logger->pushHandler(new StreamHandler($logPath));
        return $logger;
    }
}
