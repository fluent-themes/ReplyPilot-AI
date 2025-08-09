
<?php namespace Monolog;
class Logger {
    protected $name;
    protected $handlers=[];
    public function __construct($name){$this->name=$name;}
    public function pushHandler($h){$this->handlers[]=$h;}
    public function info($msg){$this->log($msg);}
    public function error($msg){$this->log($msg);}
    protected function log($msg){
        foreach($this->handlers as $h){ $h->write($msg); }
    }
}
?>
