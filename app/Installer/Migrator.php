<?php
namespace App\Installer;

use App\Support\Database;
use App\Support\Settings;
use App\Support\Analytics;
use App\Support\ResponseCache;
use App\Support\Logger;

/**
 * Auto-migration system for existing installations
 */
class Migrator
{
    protected Database $db;
    protected Logger $logger;
    protected string $currentVersion;
    protected array $migrations;
    
    public function __construct()
    {
        $this->db = new Database();
        $this->logger = new Logger();
        $this->currentVersion = $this->getCurrentVersion();
        $this->migrations = $this->loadMigrations();
    }
    
    /**
     * Check if migration is needed
     */
    public function needsMigration(): bool
    {
        $installedVersion = Settings::get('app_version', '1.0.0');
        return version_compare($installedVersion, $this->currentVersion, '<');
    }
    
    /**
     * Run auto-migration
     */
    public function migrate(): array
    {
        $results = [
            'success' => true,
            'from_version' => Settings::get('app_version', '1.0.0'),
            'to_version' => $this->currentVersion,
            'migrations_run' => [],
            'errors' => []
        ];
        
        try {
            $this->db->beginTransaction();
            
            // Ensure migration tracking table exists
            $this->createMigrationTable();
            
            // Run pending migrations
            foreach ($this->migrations as $version => $migration) {
                if ($this->shouldRunMigration($version, $results['from_version'])) {
                    $this->logger->info("Running migration for version {$version}");
                    
                    $migrationResult = $this->runMigration($migration);
                    $results['migrations_run'][] = [
                        'version' => $version,
                        'description' => $migration['description'],
                        'success' => $migrationResult['success']
                    ];
                    
                    if (!$migrationResult['success']) {
                        $results['errors'][] = "Migration {$version}: " . $migrationResult['error'];
                        $results['success'] = false;
                        break;
                    }
                    
                    $this->recordMigration($version, $migration['description']);
                }
            }
            
            if ($results['success']) {
                Settings::set('app_version', $this->currentVersion);
                $this->db->commit();
                $this->logger->info("Migration completed successfully to version {$this->currentVersion}");
            } else {
                $this->db->rollback();
                $this->logger->error("Migration failed: " . implode(', ', $results['errors']));
            }
            
        } catch (\Exception $e) {
            $this->db->rollback();
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            $this->logger->error("Migration exception: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Get current application version
     */
    protected function getCurrentVersion(): string
    {
        // Read from composer.json or version file
        $composerPath = dirname(__DIR__, 2) . '/composer.json';
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            return $composer['version'] ?? '2.0.0';
        }
        return '2.0.0';
    }
    
    /**
     * Load migration definitions
     */
    protected function loadMigrations(): array
    {
        return [
            '1.5.0' => [
                'description' => 'Add analytics tables',
                'sql' => [
                    "CREATE TABLE IF NOT EXISTS ai_analytics (
                        id int AUTO_INCREMENT PRIMARY KEY,
                        provider varchar(50) NOT NULL,
                        model varchar(100) NOT NULL,
                        message_length int DEFAULT 0,
                        response_length int DEFAULT 0,
                        tokens_used int DEFAULT 0,
                        response_time decimal(8,3) DEFAULT 0.000,
                        category varchar(50) DEFAULT 'Support',
                        confidence decimal(3,2) DEFAULT 0.00,
                        cached boolean DEFAULT false,
                        tone varchar(20) DEFAULT 'friendly',
                        product_name varchar(255) DEFAULT '',
                        success boolean DEFAULT true,
                        error_message text NULL,
                        created_at datetime NOT NULL,
                        KEY idx_created_at (created_at),
                        KEY idx_provider (provider),
                        KEY idx_success (success)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "CREATE TABLE IF NOT EXISTS license_analytics (
                        id int AUTO_INCREMENT PRIMARY KEY,
                        validator varchar(50) NOT NULL,
                        code_length int DEFAULT 0,
                        validation_time decimal(8,3) DEFAULT 0.000,
                        success boolean DEFAULT false,
                        error_message text NULL,
                        product_name varchar(255) DEFAULT '',
                        created_at datetime NOT NULL,
                        KEY idx_created_at (created_at),
                        KEY idx_validator (validator)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                ],
                'settings' => [
                    'analytics_enabled' => true,
                    'analytics_retention_days' => 90
                ]
            ],
            
            '1.8.0' => [
                'description' => 'Add response cache table',
                'sql' => [
                    "CREATE TABLE IF NOT EXISTS response_cache (
                        id int AUTO_INCREMENT PRIMARY KEY,
                        message text NOT NULL,
                        message_hash varchar(64) NOT NULL,
                        tone varchar(20) NOT NULL,
                        product_name varchar(255) NOT NULL,
                        reply text NOT NULL,
                        category varchar(50) NOT NULL,
                        confidence decimal(3,2) DEFAULT 0.00,
                        tokens_used int DEFAULT 0,
                        hit_count int DEFAULT 1,
                        expires_at datetime NOT NULL,
                        created_at datetime NOT NULL,
                        last_accessed datetime NULL,
                        KEY idx_hash_tone_product (message_hash, tone, product_name),
                        KEY idx_expires (expires_at),
                        UNIQUE KEY unique_cache (message_hash, tone, product_name)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                ],
                'settings' => [
                    'response_cache_enabled' => true,
                    'cache_ttl' => 3600,
                    'cache_similarity_threshold' => 0.85
                ]
            ],
            
            '2.0.0' => [
                'description' => 'Add system health monitoring',
                'sql' => [
                    "CREATE TABLE IF NOT EXISTS system_health_log (
                        id int AUTO_INCREMENT PRIMARY KEY,
                        metric_name varchar(100) NOT NULL,
                        metric_value decimal(10,4) NOT NULL,
                        metric_unit varchar(20) DEFAULT '',
                        status enum('healthy','warning','critical') DEFAULT 'healthy',
                        details json NULL,
                        created_at datetime NOT NULL,
                        KEY idx_metric_created (metric_name, created_at),
                        KEY idx_status (status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "CREATE TABLE IF NOT EXISTS migration_history (
                        id int AUTO_INCREMENT PRIMARY KEY,
                        version varchar(20) NOT NULL,
                        description text NOT NULL,
                        executed_at datetime NOT NULL,
                        UNIQUE KEY unique_version (version)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                ],
                'settings' => [
                    'health_monitoring_enabled' => true,
                    'health_check_interval' => 300,
                    'error_alerting_enabled' => false
                ]
            ]
        ];
    }
    
    /**
     * Check if migration should run
     */
    protected function shouldRunMigration(string $migrationVersion, string $installedVersion): bool
    {
        // Check if already run
        $result = $this->db->query(
            "SELECT id FROM migration_history WHERE version = ?",
            [$migrationVersion]
        );
        
        if (!empty($result)) {
            return false; // Already run
        }
        
        // Check version requirements
        return version_compare($installedVersion, $migrationVersion, '<');
    }
    
    /**
     * Run individual migration
     */
    protected function runMigration(array $migration): array
    {
        try {
            // Run SQL commands
            if (!empty($migration['sql'])) {
                foreach ($migration['sql'] as $sql) {
                    $this->db->query($sql);
                }
            }
            
            // Apply settings
            if (!empty($migration['settings'])) {
                foreach ($migration['settings'] as $key => $value) {
                    if (!Settings::has($key)) {
                        Settings::set($key, $value);
                    }
                }
            }
            
            // Run custom migration function if exists
            if (!empty($migration['function']) && is_callable($migration['function'])) {
                call_user_func($migration['function'], $this->db);
            }
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Record migration in history
     */
    protected function recordMigration(string $version, string $description): void
    {
        $this->db->query(
            "INSERT INTO migration_history (version, description, executed_at) VALUES (?, ?, NOW())",
            [$version, $description]
        );
    }
    
    /**
     * Create migration tracking table
     */
    protected function createMigrationTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migration_history (
            id int AUTO_INCREMENT PRIMARY KEY,
            version varchar(20) NOT NULL,
            description text NOT NULL,
            executed_at datetime NOT NULL,
            UNIQUE KEY unique_version (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->query($sql);
    }
    
    /**
     * Get migration history
     */
    public function getMigrationHistory(): array
    {
        try {
            return $this->db->query(
                "SELECT * FROM migration_history ORDER BY executed_at DESC"
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Check system health before migration
     */
    public function checkMigrationReadiness(): array
    {
        $checks = [
            'database_connection' => $this->checkDatabaseConnection(),
            'disk_space' => $this->checkDiskSpace(),
            'php_version' => $this->checkPhpVersion(),
            'required_extensions' => $this->checkRequiredExtensions(),
            'write_permissions' => $this->checkWritePermissions()
        ];
        
        $allPassed = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'] === 'ok';
        }, true);
        
        return [
            'ready' => $allPassed,
            'checks' => $checks
        ];
    }
    
    /**
     * Database connection check
     */
    protected function checkDatabaseConnection(): array
    {
        try {
            $this->db->query("SELECT 1");
            return ['status' => 'ok', 'message' => 'Database connection working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Disk space check
     */
    protected function checkDiskSpace(): array
    {
        $freeBytes = disk_free_space('.');
        $freeGB = round($freeBytes / (1024 * 1024 * 1024), 2);
        
        if ($freeGB < 1) {
            return ['status' => 'warning', 'message' => "Low disk space: {$freeGB}GB available"];
        }
        
        return ['status' => 'ok', 'message' => "{$freeGB}GB available"];
    }
    
    /**
     * PHP version check
     */
    protected function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        if (version_compare($version, '8.0.0', '<')) {
            return ['status' => 'error', 'message' => "PHP {$version} is too old (8.0+ required)"];
        }
        
        return ['status' => 'ok', 'message' => "PHP {$version}"];
    }
    
    /**
     * Required extensions check
     */
    protected function checkRequiredExtensions(): array
    {
        $required = ['pdo', 'pdo_mysql', 'json', 'curl', 'openssl'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing extensions: ' . implode(', ', $missing)];
        }
        
        return ['status' => 'ok', 'message' => 'All required extensions loaded'];
    }
    
    /**
     * Write permissions check
     */
    protected function checkWritePermissions(): array
    {
        $paths = [
            dirname(__DIR__, 2) . '/storage/logs',
            dirname(__DIR__, 2) . '/storage'
        ];
        
        $errors = [];
        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $errors[] = $path;
            }
        }
        
        if (!empty($errors)) {
            return ['status' => 'error', 'message' => 'Not writable: ' . implode(', ', $errors)];
        }
        
        return ['status' => 'ok', 'message' => 'All paths writable'];
    }
    
    /**
     * Create backup before migration
     */
    public function createBackup(): array
    {
        try {
            $backupDir = dirname(__DIR__, 2) . '/storage/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = "{$backupDir}/backup_before_migration_{$timestamp}.json";
            
            // Backup critical settings
            $backup = [
                'version' => Settings::get('app_version', '1.0.0'),
                'timestamp' => date('Y-m-d H:i:s'),
                'settings' => $this->exportSettings(),
                'database_schema' => $this->exportDatabaseSchema()
            ];
            
            file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT));
            
            return [
                'success' => true,
                'backup_file' => $backupFile,
                'size' => filesize($backupFile)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Export current settings
     */
    protected function exportSettings(): array
    {
        // Get all non-sensitive settings
        $settings = [];
        $sensitiveKeys = ['envato_personal_token', 'openai_api_key', 'app_key'];
        
        try {
            $allSettings = Settings::getAll();
            foreach ($allSettings as $key => $value) {
                if (!in_array($key, $sensitiveKeys)) {
                    $settings[$key] = $value;
                }
            }
        } catch (\Exception $e) {
            // Settings table might not exist yet
        }
        
        return $settings;
    }
    
    /**
     * Export database schema information
     */
    protected function exportDatabaseSchema(): array
    {
        try {
            $tables = $this->db->query("SHOW TABLES");
            $schema = [];
            
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $columns = $this->db->query("DESCRIBE {$tableName}");
                $schema[$tableName] = $columns;
            }
            
            return $schema;
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
