<?php
/**
 * Migration script to add analytics and cache tables to existing installations
 * Run this if you're upgrading from an earlier version
 */

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\DatabaseMock;
use App\Installer\SqlSchema;
use App\Helpers\ModeHelper;

// Prevent running in mock mode
if (ModeHelper::isMock()) {
    echo "❌ Cannot run migrations in mock mode\n";
    exit(1);
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "🔄 Starting analytics tables migration...\n\n";
    
    // Check which tables exist
    $existingTables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
    
    $tablesToCreate = [
        'ai_analytics' => SqlSchema::createAnalyticsTable(),
        'license_analytics' => SqlSchema::createLicenseAnalyticsTable(),
        'response_cache' => SqlSchema::createResponseCacheTable(),
        'performance_analytics' => SqlSchema::createPerformanceAnalyticsTable()
    ];
    
    foreach ($tablesToCreate as $tableName => $sql) {
        if (in_array($tableName, $existingTables)) {
            echo "✅ Table '{$tableName}' already exists\n";
        } else {
            echo "🔨 Creating table '{$tableName}'...";
            $pdo->exec($sql);
            echo " ✅ Created\n";
        }
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "📊 Analytics and caching features are now available.\n";
    echo "💡 Visit /admin/advanced_settings.php to configure new features.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "📋 Details: " . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
