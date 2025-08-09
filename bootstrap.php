
<?php
require __DIR__ . '/vendor/autoload.php';

use App\Core\Env;
use App\Support\Logger;
use App\Support\Database;

Env::load(__DIR__ . '/.env');

$logger = Logger::create();
$db = Database::create();

$GLOBALS['container'] = [
    'logger' => $logger,
    'db'     => $db,
];
?>
