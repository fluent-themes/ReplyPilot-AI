<?php
$base = dirname(__DIR__);
require_once $base . '/vendor/autoload.php';
if (file_exists($base . '/.env')) {
    Dotenv\Dotenv::createImmutable($base)->safeLoad();
}
$artifactsDir = $base . '/artifacts';
if (!is_dir($artifactsDir)) {
    mkdir($artifactsDir, 0777, true);
}

$timestamp = date('Ymd-His');
$zipPath = $artifactsDir . "/debug-$timestamp.zip";

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Unable to create zip file\n");
    exit(1);
}

$included = [];
$targets = ['storage/logs', 'storage/mail'];
foreach ($targets as $target) {
    $dir = $base . '/' . $target;
    if (!is_dir($dir)) {
        continue;
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile()) {
            $relPath = $target . '/' . substr($file->getPathname(), strlen($dir) + 1);
            $content = file_get_contents($file->getPathname());
            $content = preg_replace('/sk-[A-Za-z0-9]{16,}/', 'sk-***REDACTED***', $content);
            $content = preg_replace('/Authorization:\s*Bearer\s+[^\s]+/i', 'Authorization: Bearer ***REDACTED***', $content);
            $content = preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[redacted@email]', $content);
            $zip->addFromString($relPath, $content);
            $included[] = $relPath;
        }
    }
}

$envInfo = [
    'OPENAI_API_KEY' => $_ENV['OPENAI_API_KEY'] ?? '',
    'MAIL_TRANSPORT' => $_ENV['MAIL_TRANSPORT'] ?? '',
    'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? '',
];
$readme = "Timestamp: " . date('c') . "\n";
$readme .= "Environment:\n";
foreach ($envInfo as $k => $v) {
    $readme .= " - $k: $v\n";
}
$readme .= "Included paths:\n";
foreach ($included as $p) {
    $readme .= " - $p\n";
}
$zip->addFromString('README.txt', $readme);
$zip->close();

echo $zipPath . PHP_EOL;
