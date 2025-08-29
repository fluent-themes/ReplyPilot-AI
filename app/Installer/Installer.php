<?php namespace App\Installer;
use App\Support\Database;
use App\Core\Env;
use PDO;

class Installer {
    protected static function logLine(string $msg): void {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'installer.log';
        $dir = dirname($path);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        if (!is_writable($dir)) { return; } // Check write permission
        $ts = date('Y-m-d H:i:s');
        @file_put_contents($path, "[$ts] " . $msg . PHP_EOL, FILE_APPEND);
    }
    
    protected static function displayError(string $title, array $messages): void {
        echo '<div style="max-width:600px;margin:60px auto;padding:20px;background:#fff;border-left:4px solid #dc3545;">';
        echo '<h2 style="color:#dc3545;margin:0 0 15px;">⚠️ ' . htmlspecialchars($title) . '</h2>';
        echo '<div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;padding:15px;margin:15px 0;">';
        foreach ($messages as $message) {
            echo '<p style="margin:5px 0;color:#721c24;">' . htmlspecialchars($message) . '</p>';
        }
        echo '</div>';
        echo '<p style="color:#666;margin:15px 0;">Please correct the issues above and try again.</p>';
        echo '<a href="javascript:history.back()" class="btn" style="background:#6c757d;">← Go Back</a>';
        echo '<a href="?page=install" class="btn" style="margin-left:10px;">🔄 Try Again</a>';
        echo '</div>';
        echo '<style>.btn{display:inline-block;background:#007cba;color:white;text-decoration:none;padding:10px 20px;border-radius:4px;margin:5px;}</style>';
    }
    protected static function envExists(): bool {
        return file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env');
    }
    protected static function isInstalled(): bool {
        try {
            $pdo = Database::createSafe();
            if (!$pdo) {
                return false; // No database connection available
            }
            $stmt = $pdo->query("SHOW TABLES LIKE 'submissions'");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_NUM) : false;
            return $row ? true : false;
        } catch (\Throwable $e) {
            self::logLine('Installed check failed: ' . $e->getMessage());
            return false;
        }
    }
    protected static function tokenOk(): bool {
        $provided = $_GET['token'] ?? '';
        $envPresent = self::envExists();
        $expected = $envPresent ? (Env::get('INSTALL_TOKEN') ?? '') : (\defined('INSTALL_FALLBACK_TOKEN') ? INSTALL_FALLBACK_TOKEN : 'setup123');
        $ok = $expected !== '' && hash_equals((string)$expected, (string)$provided);
        self::logLine('Token check: source=' . ($envPresent?'.env':'fallback') . ' result=' . ($ok?'OK':'FAIL')); // Token value masked for security
        return $ok;
    }
    public static function run(){
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        self::logLine('Visit installer: params=' . json_encode(['page'=>$_GET['page']??null]));
        if (!self::tokenOk()) {
            http_response_code(403);
            echo 'Invalid token';
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['rpai_admin_unlocked'] = true;
        $_SESSION['rpai_admin_timeout'] = time() + 1800; // 30 minute timeout
        $installed = self::isInstalled();
        self::logLine('Installed? ' . ($installed?'yes':'no'));
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // handle POST install; accept db and advanced options
            $dbHost = trim($_POST['db_host'] ?? '');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? ''; // Don't trim passwords
            
            // Enhanced input validation
            $errors = [];
            if ($dbHost === '') $errors[] = 'Database host is required';
            if ($dbName === '') $errors[] = 'Database name is required';
            if ($dbUser === '') $errors[] = 'Database user is required';
            if (!preg_match('/^[a-zA-Z0-9._-]+$/', $dbName)) $errors[] = 'Invalid database name format';
            if (strlen($dbName) > 64) $errors[] = 'Database name too long (max 64 characters)';
            
            if (!empty($errors)) {
                self::logLine('Validation failed: ' . implode(', ', $errors));
                self::displayError('Validation Error', $errors);
                return;
            }
            
            // Advanced options
            $openaiKey = trim($_POST['openai_key'] ?? '');
            $smtpHost = trim($_POST['smtp_host'] ?? '');
            $smtpPort = trim($_POST['smtp_port'] ?? '');
            $smtpUser = trim($_POST['smtp_user'] ?? '');
            $smtpPass = trim($_POST['smtp_pass'] ?? '');
            $envatoToken = trim($_POST['envato_token'] ?? '');
            
            // Build env data with production-ready defaults
            $envData = [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_KEY' => base64_encode(random_bytes(32)),
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $dbHost,
                'DB_NAME' => $dbName,
                'DB_USER' => $dbUser,
                'DB_PASS' => $dbPass,
                'INSTALL_TOKEN' => 'setup123',
            ];
            
            // Add OpenAI config if provided
            if ($openaiKey !== '') {
                $envData['OPENAI_API_KEY'] = $openaiKey;
            }
            
            // Add SMTP config if provided
            if ($smtpHost !== '' && $smtpUser !== '') {
                $envData['MAIL_TRANSPORT'] = 'smtp';
                $envData['SMTP_HOST'] = $smtpHost;
                $envData['SMTP_PORT'] = $smtpPort ?: '587';
                $envData['SMTP_USER'] = $smtpUser;
                if ($smtpPass !== '') {
                    $envData['SMTP_PASS'] = $smtpPass;
                }
            }
            
            // Add Envato config if provided
            if ($envatoToken !== '') {
                $envData['ENVATO_PERSONAL_TOKEN'] = $envatoToken;
            }
            
            // write env
            EnvWriter::write($envData, __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env');
            self::logLine('Wrote .env with ' . count($envData) . ' keys (secrets masked).');
            
            // Set proper permissions on storage directories
            $storageDirs = [
                __DIR__ . '/../../storage',
                __DIR__ . '/../../storage/logs',
                __DIR__ . '/../../storage/mail',
                __DIR__ . '/../../storage/cache'
            ];
            
            foreach ($storageDirs as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                    self::logLine('Created directory: ' . basename($dir));
                }
                
                // Attempt to set permissions (may fail on Windows)
                if (PHP_OS_FAMILY !== 'Windows') {
                    @chmod($dir, 0755);
                    
                    // Verify writability
                    if (!is_writable($dir)) {
                        self::logLine('Warning: Directory not writable: ' . basename($dir));
                    }
                }
            }
            
            try {
                self::logLine('Starting database setup phase');
                
                // Step 1: Test initial connection
                $dsn = "mysql:host={$dbHost}"; 
                $adminPdo = null;
                try {
                    $adminPdo = new \PDO($dsn, $dbUser, $dbPass, [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_TIMEOUT => 10
                    ]);
                    self::logLine('Initial database connection successful');
                } catch (\PDOException $e) {
                    $sanitizedError = preg_replace('/password[^\s]*/i', 'password=***', $e->getMessage());
                    self::logLine('Database connection failed: ' . $sanitizedError);
                    self::displayError('Database Connection Failed', [
                        'Could not connect to database server',
                        'Please verify your host, username, and password',
                        'MySQL Error: ' . $sanitizedError
                    ]);
                    return;
                }
                
                // Step 2: Create/verify database
                try {
                    $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    self::logLine('Database created/verified with UTF8MB4 charset');
                } catch (\PDOException $e) {
                    $sanitizedError = preg_replace('/password[^\s]*/i', 'password=***', $e->getMessage());
                    self::logLine('Database creation failed: ' . $sanitizedError);
                    self::displayError('Database Creation Failed', [
                        'Could not create or access database: ' . $dbName,
                        'Please ensure the user has CREATE privileges',
                        'MySQL Error: ' . $sanitizedError
                    ]);
                    return;
                }
                
                // Step 3: Connect to specific database
                $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
                $pdo = null;
                try {
                    $pdo = new \PDO($dsn, $dbUser, $dbPass, [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                    ]);
                    self::logLine('Connected to target database successfully');
                } catch (\PDOException $e) {
                    $sanitizedError = preg_replace('/password[^\s]*/i', 'password=***', $e->getMessage());
                    self::logLine('Target database connection failed: ' . $sanitizedError);
                    self::displayError('Database Access Failed', [
                        'Could not connect to database: ' . $dbName,
                        'Database may have been created but is not accessible',
                        'MySQL Error: ' . $sanitizedError
                    ]);
                    return;
                }
                
                // Step 4: Create tables with transaction support
                try {
                    $pdo->beginTransaction();
                    $tables = SqlSchema::createAllTables();
                    $createdTables = [];
                    
                    foreach ($tables as $i => $tableSQL) {
                        try {
                            $pdo->exec($tableSQL);
                            $createdTables[] = 'Table ' . ($i + 1);
                            self::logLine('Created table: ' . ($i + 1) . '/' . count($tables));
                        } catch (\PDOException $e) {
                            $sanitizedError = preg_replace('/password[^\s]*/i', 'password=***', $e->getMessage());
                            self::logLine('Table creation failed at step ' . ($i + 1) . ': ' . $sanitizedError);
                            if ($pdo->inTransaction()) { $pdo->rollBack(); }
                            self::displayError('Table Creation Failed', [
                                'Failed to create table ' . ($i + 1) . ' of ' . count($tables),
                                'All changes have been rolled back',
                                'MySQL Error: ' . $sanitizedError
                            ]);
                            return;
                        }
                    }
                    
                    $pdo->commit();
                    self::logLine('All tables created successfully: ' . count($tables) . ' tables');
                    
                } catch (\Throwable $e) {
                    if ($pdo && $pdo->inTransaction()) {
                        $pdo->rollBack();
                        self::logLine('Transaction rolled back due to error');
                    }
                    throw $e;
                }
                
                // Success response
                echo '<div style="max-width:480px;margin:60px auto;text-align:center;">';
                echo '<h2>✅ Installation Complete!</h2>';
                echo '<p>ReplyPilot-AI has been successfully installed and configured.</p>';
                echo '<ul style="text-align:left;margin:20px 0;">';
                echo '<li>✅ Database connection verified</li>';
                echo '<li>✅ Environment file created</li>';
                echo '<li>✅ Database schema installed (' . count($tables) . ' tables)</li>';
                echo '</ul>';
                echo '<a class="btn primary" href="/admin/">Go to Admin Panel</a>';
                echo '</div>';
                
            } catch (\Throwable $e) {
                self::logLine('Installation failed with unexpected error: ' . $e->getMessage());
                self::displayError('Installation Failed', [
                    'An unexpected error occurred during installation',
                    'Please check the installer.log file for details',
                    'Error: ' . $e->getMessage()
                ]);
            }
            return;
        }
        if ($installed) {
            echo '<div style="max-width:480px;margin:60px auto;text-align:center;">';
            echo '<h2>✅ Already Installed</h2>';
            echo '<p>ReplyPilot-AI is already set up and ready to use.</p>';
            echo '<a class="btn primary" href="/admin/">Go to Admin</a>';
            echo '</div>';
            return;
        }
        
        // Check directory permissions and warn if issues
        $permissionIssues = [];
        $checkDirs = ['storage', 'storage/logs', 'storage/mail'];
        foreach ($checkDirs as $dir) {
            $fullPath = __DIR__ . '/../../' . $dir;
            if (is_dir($fullPath) && !is_writable($fullPath)) {
                $permissionIssues[] = $dir;
            }
        }
        
        // Enhanced installer form with collapsible advanced options
        echo '<!DOCTYPE html><html><head><title>ReplyPilot AI - Installer</title><style>';
        echo 'body{font-family:system-ui;max-width:600px;margin:60px auto;padding:20px;line-height:1.6}';
        echo '.form-group{margin:15px 0}.form-group label{display:block;margin-bottom:5px;font-weight:600}';
        echo '.form-group input{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px}';
        echo '.btn{background:#007cba;color:white;border:none;padding:12px 20px;border-radius:4px;cursor:pointer;font-size:14px}';
        echo '.btn:hover{background:#005a87}.advanced{margin-top:30px;border:1px solid #eee;border-radius:8px;overflow:hidden}';
        echo '.advanced-header{background:#f8f9fa;padding:15px;cursor:pointer;border-bottom:1px solid #eee}';
        echo '.advanced-content{padding:20px;display:none}.advanced.open .advanced-content{display:block}';
        echo '</style></head><body>';
        echo '<h1>🚀 ReplyPilot AI Installer</h1>';
        echo '<p>Let\'s set up your AI-powered support system.</p>';
        
        if (!empty($permissionIssues)) {
            echo '<div style="background:#fff3cd;border:1px solid #ffc107;padding:10px;margin:20px 0;border-radius:4px;">';
            echo '<strong>⚠️ Permission Notice:</strong> The following directories need write permissions: ';
            echo implode(', ', $permissionIssues);
            echo '</div>';
        }
        echo '<form method="post">';
        echo '<h3>Database Configuration</h3>';
        echo '<div class="form-group"><label>Database Host</label><input name="db_host" value="127.0.0.1" required></div>';
        echo '<div class="form-group"><label>Database Name</label><input name="db_name" value="replypilot" required></div>';
        echo '<div class="form-group"><label>Database User</label><input name="db_user" value="replypilot" required></div>';
        echo '<div class="form-group"><label>Database Password</label><input name="db_pass" type="password"></div>';
        
        echo '<div class="advanced" id="advanced">';
        echo '<div class="advanced-header" onclick="toggleAdvanced()">⚙️ Advanced Options (Optional)</div>';
        echo '<div class="advanced-content">';
        echo '<h4>OpenAI Configuration</h4>';
        echo '<div class="form-group"><label>OpenAI API Key (leave empty for mock mode)</label><input name="openai_key" placeholder="sk-..."></div>';
        echo '<h4>Email Configuration</h4>';
        echo '<div class="form-group"><label>SMTP Host</label><input name="smtp_host" placeholder="smtp.gmail.com"></div>';
        echo '<div class="form-group"><label>SMTP Port</label><input name="smtp_port" value="587" type="number"></div>';
        echo '<div class="form-group"><label>SMTP User</label><input name="smtp_user" placeholder="your-email@domain.com"></div>';
        echo '<div class="form-group"><label>SMTP Password</label><input name="smtp_pass" type="password"></div>';
        echo '<h4>Envato Integration</h4>';
        echo '<div class="form-group"><label>Envato Personal Token (for purchase validation)</label><input name="envato_token" placeholder="Your Envato API token"></div>';
        echo '</div></div>';
        
        echo '<button class="btn" type="submit" style="margin-top:20px;">🚀 Install ReplyPilot AI</button>';
        echo '</form>';
        echo '<script>function toggleAdvanced(){const el=document.getElementById("advanced");el.classList.toggle("open")}</script>';
        echo '</body></html>';
    }
}
?>
