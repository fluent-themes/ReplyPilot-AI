
<?php namespace App\Installer;
use App\Support\Database;
use App\Core\Env;
use PDO;

class Installer {
    public static function run(){
        if($_GET['token'] ?? '' !== Env::get('INSTALL_TOKEN')){
            die('Invalid token');
        }
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $dbHost=$_POST['db_host']; $dbName=$_POST['db_name']; $dbUser=$_POST['db_user']; $dbPass=$_POST['db_pass'];
            EnvWriter::write([
                'APP_ENV' => 'production',
                'DB_HOST' => $dbHost,
                'DB_NAME' => $dbName,
                'DB_USER' => $dbUser,
                'DB_PASS' => $dbPass,
            ], __DIR__.'/../../.env');
            echo 'Env written. Importing DB...<br>';
            $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$dbName}`;");
            $pdo->exec(SqlSchema::createTable());
            echo 'Done!';
            return;
        }
        echo '<form method="post">
        <input name="db_host" placeholder="DB Host" required><br>
        <input name="db_name" placeholder="DB Name" required><br>
        <input name="db_user" placeholder="DB User" required><br>
        <input name="db_pass" placeholder="DB Pass"><br>
        <button>Install</button>
        </form>';
    }
}
?>
