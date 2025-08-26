# ReplyPilot AI - Debug & Troubleshooting Guide

## Table of Contents

1. [Debug Mode Configuration](#debug-mode-configuration)
2. [Common Errors & Solutions](#common-errors--solutions)
3. [Logging System](#logging-system)
4. [Database Troubleshooting](#database-troubleshooting)
5. [Session & Authentication Issues](#session--authentication-issues)
6. [JSON Response Issues](#json-response-issues)
7. [File Permission Problems](#file-permission-problems)
8. [Performance Debugging](#performance-debugging)
9. [AI Provider Debugging](#ai-provider-debugging)
10. [Developer Tools](#developer-tools)

## Debug Mode Configuration

### Enabling Debug Mode

Edit your `.env` file to enable detailed error reporting:

```bash
# Debug Settings
APP_DEBUG=true
APP_ENV=development
LOG_LEVEL=debug
DISPLAY_ERRORS=true
ERROR_REPORTING=E_ALL
```

### PHP Configuration for Debugging

Add to `bootstrap.php` or `.htaccess`:

```php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/logs/php_errors.log');

// Enable assertions
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_BAIL, 1);
```

### Debug Headers

When debug mode is enabled, responses include:

```
X-Debug-Mode: enabled
X-Execution-Time: 0.0234s
X-Memory-Usage: 2.34MB
X-Memory-Peak: 3.12MB
X-Database-Queries: 12
X-Cache-Status: MISS
```

## Common Errors & Solutions

### 500 Internal Server Error

**Symptoms**: Blank page or generic server error

**Debug Steps**:

1. Check PHP error log:
```bash
tail -f storage/logs/error.log
tail -f /var/log/apache2/error.log  # System log
```

2. Enable error display temporarily:
```php
// Add to top of index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

3. Common causes:
- Missing PHP extensions
- Syntax errors in PHP files
- Memory limit exceeded
- Timeout issues

**Solutions**:
```bash
# Check PHP extensions
php -m | grep -E "pdo|curl|json|mbstring"

# Increase memory limit
echo "memory_limit = 256M" >> php.ini

# Check syntax
php -l bootstrap.php
```

### Database Connection Failed

**Error**: "SQLSTATE[HY000] [2002] Connection refused"

**Debug Steps**:

1. Test connection manually:
```bash
mysql -h localhost -u replypilot_user -p replypilot_db
```

2. Check credentials in `.env`:
```bash
DB_HOST=localhost  # Try 127.0.0.1 instead
DB_PORT=3306
DB_NAME=replypilot_db
DB_USER=replypilot_user
DB_PASS=yourpassword
```

3. Verify MySQL service:
```bash
systemctl status mysql
netstat -an | grep 3306
```

**Solutions**:
```php
// Add debug output to database connection
try {
    $pdo = new PDO($dsn, $user, $pass);
    error_log("Database connected successfully");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    error_log("DSN: " . $dsn);
    // Never log passwords!
}
```

### CSRF Token Mismatch

**Error**: "CSRF token validation failed"

**Debug Steps**:

1. Check session status:
```php
var_dump(session_status());
var_dump($_SESSION['csrf_token']);
var_dump($_POST['csrf_token']);
```

2. Verify token generation:
```php
// In form generation
error_log("Generated CSRF: " . $_SESSION['csrf_token']);

// In validation
error_log("Session CSRF: " . $_SESSION['csrf_token']);
error_log("Posted CSRF: " . $_POST['csrf_token']);
```

**Solutions**:
```php
// Ensure session starts before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

## Logging System

### Log File Locations

```
storage/
├── logs/
│   ├── error.log          # PHP errors and exceptions
│   ├── debug.log          # Debug messages
│   ├── access.log         # Request logs
│   ├── api.log            # API requests/responses
│   ├── ai_provider.log    # AI provider interactions
│   ├── email.log          # Email sending logs
│   └── installer.log      # Installation process logs
```

### Custom Logging

```php
// Create custom logger
class DebugLogger {
    private $logFile;
    
    public function __construct($filename = 'debug.log') {
        $this->logFile = __DIR__ . '/storage/logs/' . $filename;
    }
    
    public function log($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context ? json_encode($context) : '';
        $logMessage = "[$timestamp] $message $contextStr\n";
        
        error_log($logMessage, 3, $this->logFile);
    }
    
    public function logRequest() {
        $this->log('Request', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }
}

// Usage
$logger = new DebugLogger();
$logger->log('Processing submission', ['id' => 123]);
```

### Log Rotation

```bash
# Create logrotate configuration
cat > /etc/logrotate.d/replypilot << EOF
/path/to/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
EOF
```

## Database Troubleshooting

### Query Debugging

```php
// Enable query logging
class DebugPDO extends PDO {
    public function query($sql) {
        $start = microtime(true);
        $result = parent::query($sql);
        $time = microtime(true) - $start;
        
        error_log(sprintf(
            "Query (%.4fs): %s",
            $time,
            $sql
        ));
        
        return $result;
    }
}
```

### Slow Query Detection

```php
// Log slow queries
$threshold = 0.1; // 100ms

$start = microtime(true);
$stmt = $pdo->query($sql);
$duration = microtime(true) - $start;

if ($duration > $threshold) {
    error_log("SLOW QUERY ({$duration}s): $sql");
}
```

### Database Performance Check

```sql
-- Check table sizes
SELECT 
    table_name,
    round(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'replypilot_db'
ORDER BY size_mb DESC;

-- Check missing indexes
SELECT 
    statements_with_full_table_scans,
    statements_with_sorting,
    statements_with_temp_tables
FROM performance_schema.events_statements_summary_global_by_event_name
WHERE event_name LIKE 'statement/sql/%'
ORDER BY statements_with_full_table_scans DESC;
```

## Session & Authentication Issues

### Session Debugging

```php
// Session debug info
function debugSession() {
    $info = [
        'id' => session_id(),
        'status' => session_status(),
        'save_path' => session_save_path(),
        'name' => session_name(),
        'cookie_params' => session_get_cookie_params(),
        'data' => $_SESSION
    ];
    
    error_log('Session Debug: ' . json_encode($info, JSON_PRETTY_PRINT));
}

// Check session files
$sessionPath = session_save_path();
$sessionFiles = glob($sessionPath . '/sess_*');
error_log('Active sessions: ' . count($sessionFiles));
```

### Authentication Troubleshooting

```php
// Debug admin authentication
function debugAuth() {
    $checks = [
        'session_exists' => session_status() === PHP_SESSION_ACTIVE,
        'admin_unlocked' => $_SESSION['rpai_admin_unlocked'] ?? false,
        'timeout_check' => (time() - ($_SESSION['last_activity'] ?? 0)) < 1800,
        'ip_match' => $_SESSION['ip'] === $_SERVER['REMOTE_ADDR']
    ];
    
    foreach ($checks as $check => $result) {
        error_log("Auth check $check: " . ($result ? 'PASS' : 'FAIL'));
    }
    
    return $checks;
}
```

## JSON Response Issues

### Common JSON Problems

**Issue**: "SyntaxError: Unexpected token < in JSON"

**Cause**: PHP error or warning output before JSON

**Debug**:
```php
// Clear any output before JSON
ob_clean();

// Ensure no BOM
if (ob_get_level()) {
    ob_end_clean();
}

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Validate JSON encoding
$data = ['status' => 'success'];
$json = json_encode($data);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON encode error: ' . json_last_error_msg());
    http_response_code(500);
    die('{"error":"JSON encoding failed"}');
}

echo $json;
exit; // Prevent any additional output
```

### JSON Debugging Helper

```php
function jsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set status code
    http_response_code($statusCode);
    
    // Set headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Encode with error checking
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if ($json === false) {
        error_log('JSON encode error: ' . json_last_error_msg());
        error_log('Data: ' . print_r($data, true));
        
        $json = json_encode([
            'error' => 'Internal server error',
            'debug' => json_last_error_msg()
        ]);
    }
    
    // Add debug headers
    if (getenv('APP_DEBUG') === 'true') {
        header('X-Debug-Memory: ' . memory_get_peak_usage(true));
        header('X-Debug-Time: ' . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4));
    }
    
    echo $json;
    exit;
}
```

## File Permission Problems

### Permission Check Script

```php
function checkPermissions() {
    $directories = [
        'storage' => 0755,
        'storage/logs' => 0755,
        'storage/mail' => 0755,
        'storage/cache' => 0755,
        'storage/sessions' => 0755
    ];
    
    $issues = [];
    
    foreach ($directories as $dir => $requiredPerms) {
        $path = __DIR__ . '/' . $dir;
        
        if (!file_exists($path)) {
            $issues[] = "Missing: $dir";
            continue;
        }
        
        if (!is_writable($path)) {
            $issues[] = "Not writable: $dir";
        }
        
        $perms = fileperms($path) & 0777;
        if ($perms !== $requiredPerms) {
            $issues[] = sprintf(
                "Wrong permissions on %s: %o (should be %o)",
                $dir,
                $perms,
                $requiredPerms
            );
        }
    }
    
    return $issues;
}

// Run check
$issues = checkPermissions();
if ($issues) {
    error_log('Permission issues: ' . implode(', ', $issues));
}
```

### Fix Permissions (Linux/Unix)

```bash
#!/bin/bash
# fix-permissions.sh

WEBUSER="www-data"  # or apache, nginx, etc.

# Set directory permissions
find storage -type d -exec chmod 755 {} \;

# Set file permissions
find storage -type f -exec chmod 644 {} \;

# Set ownership
chown -R $WEBUSER:$WEBUSER storage/

# Make specific directories writable
chmod 775 storage/logs
chmod 775 storage/mail
chmod 775 storage/cache
chmod 775 storage/sessions

echo "Permissions fixed"
```

## Performance Debugging

### Execution Time Profiling

```php
class PerformanceProfiler {
    private $markers = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->mark('start');
    }
    
    public function mark($label) {
        $this->markers[$label] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true)
        ];
    }
    
    public function getReport() {
        $report = [];
        $prevTime = $this->startTime;
        $prevMemory = 0;
        
        foreach ($this->markers as $label => $data) {
            $report[$label] = [
                'elapsed' => round($data['time'] - $this->startTime, 4),
                'delta' => round($data['time'] - $prevTime, 4),
                'memory' => $this->formatBytes($data['memory']),
                'memory_delta' => $this->formatBytes($data['memory'] - $prevMemory)
            ];
            
            $prevTime = $data['time'];
            $prevMemory = $data['memory'];
        }
        
        return $report;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}

// Usage
$profiler = new PerformanceProfiler();

$profiler->mark('database_connect');
// ... database connection code ...

$profiler->mark('data_fetch');
// ... data fetching code ...

$profiler->mark('ai_processing');
// ... AI processing code ...

$profiler->mark('response_generation');
// ... response generation ...

error_log('Performance Report: ' . json_encode($profiler->getReport()));
```

### Memory Usage Debugging

```php
// Memory usage tracker
function trackMemoryUsage($label = '') {
    static $lastMemory = 0;
    
    $current = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);
    $delta = $current - $lastMemory;
    
    error_log(sprintf(
        "Memory %s: Current=%s, Peak=%s, Delta=%s",
        $label,
        formatBytes($current),
        formatBytes($peak),
        ($delta > 0 ? '+' : '') . formatBytes($delta)
    ));
    
    $lastMemory = $current;
}

// Check for memory leaks
function detectMemoryLeaks() {
    $iterations = 100;
    $startMemory = memory_get_usage(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        // Your operation here
        processSubmission($testData);
    }
    
    $endMemory = memory_get_usage(true);
    $leak = ($endMemory - $startMemory) / $iterations;
    
    if ($leak > 1024) { // More than 1KB per iteration
        error_log("Possible memory leak: " . formatBytes($leak) . " per operation");
    }
}
```

## AI Provider Debugging

### OpenAI Debug

```php
function debugOpenAI($apiKey, $message) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7,
        'max_tokens' => 100
    ];
    
    // Log request
    error_log('OpenAI Request: ' . json_encode($data));
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Capture verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    // Get verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    curl_close($ch);
    
    // Log response and debug info
    error_log('OpenAI Response Code: ' . $httpCode);
    error_log('OpenAI Response: ' . $response);
    if ($curlError) {
        error_log('CURL Error: ' . $curlError);
    }
    error_log('CURL Verbose: ' . $verboseLog);
    
    return json_decode($response, true);
}
```

### Provider Response Validation

```php
function validateAIResponse($provider, $response) {
    $validators = [
        'openai' => function($r) {
            return isset($r['choices'][0]['message']['content']);
        },
        'claude' => function($r) {
            return isset($r['content'][0]['text']);
        },
        'gemini' => function($r) {
            return isset($r['candidates'][0]['content']['parts'][0]['text']);
        }
    ];
    
    if (!isset($validators[$provider])) {
        error_log("Unknown provider: $provider");
        return false;
    }
    
    $isValid = $validators[$provider]($response);
    
    if (!$isValid) {
        error_log("Invalid $provider response structure: " . json_encode($response));
    }
    
    return $isValid;
}
```

## Developer Tools

### Debug Dashboard

Create `admin/debug.php`:

```php
<?php
require_once '../bootstrap.php';
require_once 'guard.php';

// Only in debug mode
if (getenv('APP_DEBUG') !== 'true') {
    die('Debug mode not enabled');
}

// Gather debug information
$debugInfo = [
    'PHP Version' => PHP_VERSION,
    'Loaded Extensions' => get_loaded_extensions(),
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time'),
    'Post Max Size' => ini_get('post_max_size'),
    'Upload Max Filesize' => ini_get('upload_max_filesize'),
    'Session Save Path' => session_save_path(),
    'Temp Directory' => sys_get_temp_dir(),
    'Include Path' => get_include_path(),
    'Disabled Functions' => ini_get('disable_functions'),
    'OPcache Enabled' => function_exists('opcache_get_status') && opcache_get_status(),
];

// Check database
try {
    $pdo = Database::getInstance()->getConnection();
    $debugInfo['Database'] = 'Connected';
    $debugInfo['Database Version'] = $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (Exception $e) {
    $debugInfo['Database'] = 'Error: ' . $e->getMessage();
}

// Display debug info
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Dashboard</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Debug Dashboard</h1>
    
    <h2>System Information</h2>
    <table>
        <?php foreach ($debugInfo as $key => $value): ?>
        <tr>
            <th><?= htmlspecialchars($key) ?></th>
            <td><?= is_array($value) ? implode(', ', $value) : htmlspecialchars($value) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>Recent Errors</h2>
    <pre><?php
    $errorLog = '../storage/logs/error.log';
    if (file_exists($errorLog)) {
        echo htmlspecialchars(tail($errorLog, 20));
    }
    ?></pre>
    
    <h2>Environment Variables</h2>
    <pre><?= htmlspecialchars(print_r($_ENV, true)) ?></pre>
    
    <h2>Session Data</h2>
    <pre><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
</body>
</html>
```

### Browser Console Debugging

```javascript
// Add to your JavaScript files
const DEBUG = true;

function debugLog(...args) {
    if (DEBUG && console && console.log) {
        console.log('[ReplyPilot Debug]', ...args);
    }
}

// Intercept AJAX responses
if (DEBUG) {
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        debugLog('Fetch request:', args);
        
        return originalFetch.apply(this, args)
            .then(response => {
                debugLog('Fetch response:', response.status, response.headers);
                return response;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                throw error;
            });
    };
}

// Monitor form submissions
if (DEBUG) {
    document.addEventListener('submit', function(e) {
        debugLog('Form submission:', {
            action: e.target.action,
            method: e.target.method,
            data: new FormData(e.target)
        });
    });
}
```

### XDebug Configuration

```ini
; xdebug.ini
zend_extension=xdebug.so
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.idekey=PHPSTORM
xdebug.log=/tmp/xdebug.log
xdebug.show_error_trace=1
```

## Quick Debug Checklist

When debugging issues, check in this order:

1. ☐ PHP error logs (`storage/logs/error.log`)
2. ☐ Web server error logs (`/var/log/apache2/error.log`)
3. ☐ Database connection (`.env` credentials)
4. ☐ File permissions (`storage/` directories)
5. ☐ PHP extensions (`php -m`)
6. ☐ Session configuration (`session_save_path()`)
7. ☐ Memory limits (`php.ini`)
8. ☐ Network connectivity (API providers)
9. ☐ CSRF tokens (form submissions)
10. ☐ JSON response format (AJAX calls)

---

**Version**: 1.0.0  
**Last Updated**: August 2025  
**Support**: For debugging assistance, contact support@fluentthemes.com