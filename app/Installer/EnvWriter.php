
<?php namespace App\Installer;
class EnvWriter {
    public static function write(array $vars, $path){
        $content = '';
        foreach($vars as $k=>$v){
            $content .= $k.'='.$v.PHP_EOL;
        }
        file_put_contents($path, $content);
    }
}
?>
