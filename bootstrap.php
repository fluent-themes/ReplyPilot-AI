<?php
require __DIR__ . '/vendor/autoload.php';

use App\Core\Env;
use App\Support\Database;
use App\Support\DatabaseMock;
use App\Support\Logger;

Env::load(__DIR__ . '/.env');

$logger = Logger::create();
$useMockDb = Env::get('DB_CONNECTION') === 'none';
// PRODUCTION NOTE:
// To enable real database: set DB_CONNECTION and DB_* variables in .env
$db = $useMockDb ? DatabaseMock::create() : Database::create();

$GLOBALS['container'] = [
    'logger' => $logger,
    'db'     => $db,
];
