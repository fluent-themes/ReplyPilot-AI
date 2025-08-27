<?php
require __DIR__ . '/../bootstrap.php';

use App\Installer\Migrator;
use App\Support\Logger;

// Security check - only allow in development or with specific token
if (!isset($_GET['token']) || $_GET['token'] !== 'migrate_' . date('Ymd')) {
    http_response_code(403);
    die('Access denied. Use token: migrate_' . date('Ymd'));
}

$migrator = new Migrator();
$logger = new Logger();

// Set headers for real-time output
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auto Migration - ReplyPilot AI</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid; }
        .status.success { background: #d4edda; border-color: #28a745; color: #155724; }
        .status.error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .status.warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .status.info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .progress { background: #e9ecef; border-radius: 4px; height: 20px; margin: 15px 0; overflow: hidden; }
        .progress-bar { background: #007bff; height: 100%; transition: width 0.3s ease; text-align: center; line-height: 20px; color: white; font-size: 12px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #495057; margin-top: 30px; }
        .check-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; }
        .check-status { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .check-status.ok { background: #d4edda; color: #155724; }
        .check-status.warning { background: #fff3cd; color: #856404; }
        .check-status.error { background: #f8d7da; color: #721c24; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 ReplyPilot AI Auto Migration</h1>
        
        <?php
        // Step 1: Check if migration is needed
        echo "<h2>Step 1: Migration Check</h2>";
        
        if (!$migrator->needsMigration()) {
            echo '<div class="status success">✅ No migration needed. System is up to date.</div>';
            echo '<a href="../admin/index.php" class="btn btn-success">Go to Admin Dashboard</a>';
            exit;
        }
        
        $currentVersion = $migrator->getCurrentVersion();
        $installedVersion = \App\Support\Settings::get('app_version', '1.0.0');
        
        echo "<div class='status info'>📋 Migration needed from version <strong>{$installedVersion}</strong> to <strong>{$currentVersion}</strong></div>";
        
        // Step 2: System readiness check
        echo "<h2>Step 2: System Readiness Check</h2>";
        
        $readiness = $migrator->checkMigrationReadiness();
        
        foreach ($readiness['checks'] as $check => $result) {
            echo "<div class='check-item'>";
            echo "<span>" . ucwords(str_replace('_', ' ', $check)) . "</span>";
            echo "<span class='check-status {$result['status']}'>{$result['message']}</span>";
            echo "</div>";
        }
        
        if (!$readiness['ready']) {
            echo '<div class="status error">❌ System not ready for migration. Please fix the issues above.</div>';
            exit;
        }
        
        echo '<div class="status success">✅ System ready for migration</div>';
        
        // Step 3: Create backup
        echo "<h2>Step 3: Creating Backup</h2>";
        
        $backup = $migrator->createBackup();
        if ($backup['success']) {
            $backupSize = round($backup['size'] / 1024, 2);
            echo "<div class='status success'>💾 Backup created: {$backupSize}KB</div>";
            echo "<pre>Backup file: {$backup['backup_file']}</pre>";
        } else {
            echo "<div class='status warning'>⚠️ Backup failed: {$backup['error']}</div>";
            echo "<div class='status info'>Continuing without backup...</div>";
        }
        
        // Step 4: Run migration
        echo "<h2>Step 4: Running Migration</h2>";
        echo '<div class="progress"><div class="progress-bar" style="width: 0%">Starting...</div></div>';
        
        // Flush output for real-time display
        if (ob_get_level()) ob_end_flush();
        flush();
        
        // Add JavaScript for progress updates
        echo '<script>
            function updateProgress(percent, message) {
                const bar = document.querySelector(".progress-bar");
                bar.style.width = percent + "%";
                bar.textContent = message;
            }
        </script>';
        
        echo '<script>updateProgress(10, "Initializing migration...");</script>';
        flush();
        
        $migrationResult = $migrator->migrate();
        
        echo '<script>updateProgress(100, "Migration completed");</script>';
        flush();
        
        // Step 5: Display results
        echo "<h2>Step 5: Migration Results</h2>";
        
        if ($migrationResult['success']) {
            echo '<div class="status success">🎉 Migration completed successfully!</div>';
            
            if (!empty($migrationResult['migrations_run'])) {
                echo "<h3>Migrations Applied:</h3>";
                foreach ($migrationResult['migrations_run'] as $migration) {
                    $icon = $migration['success'] ? '✅' : '❌';
                    echo "<div class='check-item'>";
                    echo "<span>{$icon} Version {$migration['version']}</span>";
                    echo "<span>{$migration['description']}</span>";
                    echo "</div>";
                }
            }
            
            echo '<div style="margin-top: 30px;">';
            echo '<a href="../admin/index.php" class="btn btn-success">Go to Admin Dashboard</a>';
            echo '<a href="../public/index.php" class="btn btn-primary">Go to Contact Form</a>';
            echo '</div>';
            
        } else {
            echo '<div class="status error">❌ Migration failed</div>';
            
            if (!empty($migrationResult['errors'])) {
                echo "<h3>Errors:</h3>";
                foreach ($migrationResult['errors'] as $error) {
                    echo "<div class='status error'>• {$error}</div>";
                }
            }
            
            echo '<div style="margin-top: 30px;">';
            echo '<a href="?token=' . $_GET['token'] . '" class="btn btn-danger">Retry Migration</a>';
            echo '</div>';
        }
        
        // Migration history
        echo "<h2>Migration History</h2>";
        $history = $migrator->getMigrationHistory();
        
        if (!empty($history)) {
            echo "<pre>";
            foreach ($history as $record) {
                echo "✅ {$record['version']} - {$record['description']} ({$record['executed_at']})\n";
            }
            echo "</pre>";
        } else {
            echo '<div class="status info">No migration history available</div>';
        }
        
        $logger->info("Auto migration completed", [
            'success' => $migrationResult['success'],
            'from_version' => $migrationResult['from_version'],
            'to_version' => $migrationResult['to_version'],
            'migrations_count' => count($migrationResult['migrations_run'])
        ]);
        ?>
    </div>
</body>
</html>
