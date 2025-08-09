<?php
namespace App\Installer;

use App\Support\Logger;
use App\Core\Env;

class InstallerMock
{
    public static function run(): void
    {
        if(($_GET['token'] ?? '') !== Env::get('INSTALL_TOKEN')){
            die('Invalid token');
        }
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $dbName = $_POST['db_name'] ?? '';
            $envDir = __DIR__ . '/../../storage/mock_env';
            if (!is_dir($envDir)) {
                mkdir($envDir, 0775, true);
            }
            $envContent = "APP_ENV=production\nDB_NAME={$dbName}\n";
            file_put_contents($envDir . '/' . time() . '.env', $envContent);
            Logger::create()->info("Mock installer ran with DB name: {$dbName}");
            echo 'Env written. Importing DB...<br>';
            echo 'Done!';
            return;
        }
        echo '<form method="post">'
            .'        <input name="db_host" placeholder="DB Host" required><br>'
            .'        <input name="db_name" placeholder="DB Name" required><br>'
            .'        <input name="db_user" placeholder="DB User" required><br>'
            .'        <input name="db_pass" placeholder="DB Pass"><br>'
            .'        <button>Install</button>'
            .'        </form>';
    }
}
