
<?php namespace App\Support;
use App\Core\Env;
use PDO;

class Database {
    public static function create(){
        $dsn = 'mysql:host='.Env::get('DB_HOST').';dbname='.Env::get('DB_NAME').';charset=utf8mb4';
        $pdo = new PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $pdo;
    }
}
?>
