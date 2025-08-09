
<?php namespace Monolog\Handler;
class StreamHandler {
    protected $path;
    public function __construct($path){$this->path=$path;}
    public function write($msg){
        file_put_contents($this->path, '['.date('c').'] '.$msg.PHP_EOL, FILE_APPEND);
    }
}
?>
