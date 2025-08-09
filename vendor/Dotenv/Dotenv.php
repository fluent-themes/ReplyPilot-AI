
<?php namespace Dotenv;
class Dotenv {
    protected $dir;
    public static function createImmutable($dir){ return new self($dir); }
    public function __construct($dir){$this->dir=$dir;}
    public function load(){
        $file=$this->dir.'/.env';
        if(!file_exists($file)){ return; }
        foreach(file($file, FILE_IGNORE_NEW_LINES) as $line){
            if(trim($line)==='' || str_starts_with(trim($line),'#')) continue;
            putenv($line);
            [$k,$v]=explode('=',$line,2);
            $_ENV[$k]=$v;
        }
    }
}
?>
