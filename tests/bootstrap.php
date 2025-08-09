<?php
require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.example')->load();
putenv('OPENAI_API_KEY=MOCK_MODE');
putenv('MAIL_TRANSPORT=file');
putenv('DB_CONNECTION=none');
$_ENV['OPENAI_API_KEY'] = 'MOCK_MODE';
$_ENV['MAIL_TRANSPORT'] = 'file';
$_ENV['DB_CONNECTION'] = 'none';

require __DIR__ . '/../bootstrap.php';
