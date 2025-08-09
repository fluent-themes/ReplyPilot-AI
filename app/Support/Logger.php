
<?php namespace App\Support;
use App\Core\Env;
use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

class Logger {
    public static function create(){
        $logPath = __DIR__ . '/../../logs/app.log';
        if(!is_dir(dirname($logPath))){
            mkdir(dirname($logPath), 0775, true);
        }
        $logger = new MonoLogger('app');
        $logger->pushHandler(new StreamHandler($logPath));
        return $logger;
    }
}
?>
