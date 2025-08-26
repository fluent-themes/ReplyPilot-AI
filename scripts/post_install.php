<?php
$root = __DIR__ . '/..';
$env = $root . '/.env';
$envProd = $root . '/.env.production';
if (!file_exists($env) && file_exists($envProd)) {
    copy($envProd, $env);
    echo "Created .env from .env.production".PHP_EOL;
}
