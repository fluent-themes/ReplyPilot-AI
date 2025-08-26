# ReplyPilot AI - System Architecture

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [Core Components](#core-components)
4. [Request Lifecycle](#request-lifecycle)
5. [Database Schema](#database-schema)
6. [Security Architecture](#security-architecture)
7. [AI Integration Layer](#ai-integration-layer)
8. [Session Management](#session-management)
9. [Error Handling](#error-handling)
10. [Performance Considerations](#performance-considerations)

## Architecture Overview

ReplyPilot AI follows a modular MVC-inspired architecture with Repository pattern for data access. The system is designed for high availability, security, and scalability.

### Design Principles

- **Separation of Concerns**: Clear boundaries between presentation, business logic, and data layers
- **Dependency Injection**: Loosely coupled components for flexibility
- **Repository Pattern**: Abstract data access layer
- **Service Layer**: Business logic encapsulation
- **Guard Pattern**: Authentication and authorization checks
- **Factory Pattern**: AI provider instantiation

### System Layers

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│    (HTML, CSS, JavaScript, AJAX)        │
├─────────────────────────────────────────┤
│         Application Layer               │
│    (Controllers, Request Handlers)      │
├─────────────────────────────────────────┤
│         Business Logic Layer            │
│    (Services, AI Providers, Mailer)     │
├─────────────────────────────────────────┤
│         Data Access Layer               │
│    (Repositories, Database, Cache)      │
├─────────────────────────────────────────┤
│         Infrastructure Layer            │
│    (Database, File System, Sessions)    │
└─────────────────────────────────────────┘
```

## Directory Structure

```
replypilot-ai/
├── admin/                      # Admin panel components
│   ├── guard.php              # Authentication middleware
│   ├── index.php              # Dashboard
│   ├── settings.php           # Settings management
│   ├── update_*.php           # Action handlers
│   └── test_provider.php      # API testing
│
├── app/                       # Core application code
│   ├── Core/                  # Core utilities
│   │   ├── Database.php       # Database singleton
│   │   ├── Env.php           # Environment manager
│   │   └── Session.php        # Session handler
│   │
│   ├── Installer/             # Installation system
│   │   ├── Installer.php      # Installation logic
│   │   └── EnvWriter.php      # Environment file writer
│   │
│   ├── Providers/             # AI provider implementations
│   │   ├── OpenAIProvider.php
│   │   ├── ClaudeProvider.php
│   │   └── GeminiProvider.php
│   │
│   ├── Repository/            # Data access layer
│   │   └── SubmissionRepository.php
│   │
│   └── Support/               # Support utilities
│       ├── Mailer.php         # Email functionality
│       ├── Settings.php       # Settings manager
│       └── LicenseValidator.php
│
├── public/                    # Public-facing components
│   ├── index.php             # Main entry point
│   ├── ajax-submit.php       # AJAX submission handler
│   ├── installer.php         # Installation interface
│   ├── ticket.php            # Ticket viewing
│   └── thank-you.php         # Confirmation page
│
├── storage/                   # Writable storage
│   ├── cache/                # Response cache
│   ├── logs/                 # Application logs
│   ├── mail/                 # Email queue
│   └── sessions/             # Session files
│
├── scripts/                   # Utility scripts
│   └── auto_migrate.php      # Database migration
│
├── docs/                      # Documentation
├── tests/                     # Test suites
│
├── bootstrap.php              # Application bootstrap
├── .env                       # Environment configuration
└── composer.json              # Dependency management
```

## Core Components

### Bootstrap System

**File**: `bootstrap.php`

Responsibilities:
- Define application constants
- Set up autoloading
- Initialize error handling
- Load environment configuration
- Configure timezone and locale

```php
// Core initialization sequence
define('APP_ROOT', __DIR__);
require_once 'app/Core/Env.php';
Env::load();
spl_autoload_register([Autoloader::class, 'load']);
error_reporting(getenv('APP_DEBUG') ? E_ALL : 0);
```

### Database Layer

**File**: `app/Core/Database.php`

Singleton pattern for database connections:

```php
class Database {
    private static $instance = null;
    private $connection;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST'),
            getenv('DB_NAME')
        );
        
        $this->connection = new PDO(
            $dsn,
            getenv('DB_USER'),
            getenv('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
}
```

### Repository Pattern

**File**: `app/Repository/SubmissionRepository.php`

Data access abstraction:

```php
class SubmissionRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO submissions (name, email, message, ref) 
             VALUES (:name, :email, :message, :ref)"
        );
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }
    
    public function findByRef(string $ref): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM submissions WHERE ref = :ref"
        );
        $stmt->execute(['ref' => $ref]);
        return $stmt->fetch() ?: null;
    }
}
```

### Service Layer

AI provider abstraction:

```php
interface AIProviderInterface {
    public function generateReply(string $message, array $context): string;
    public function testConnection(): bool;
    public function getName(): string;
}

class AIProviderFactory {
    public static function create(string $provider): AIProviderInterface {
        switch ($provider) {
            case 'openai':
                return new OpenAIProvider();
            case 'claude':
                return new ClaudeProvider();
            case 'gemini':
                return new GeminiProvider();
            default:
                throw new InvalidArgumentException("Unknown provider: $provider");
        }
    }
}
```

## Request Lifecycle

### Public Submission Flow

```
1. User submits form → public/index.php
   ↓
2. Validation & CSRF check
   ↓
3. Create submission in database
   ↓
4. Generate unique ticket reference
   ↓
5. Queue for AI processing (async)
   ↓
6. Send email notifications
   ↓
7. Redirect to thank you page
```

### AJAX Submission Flow

```
1. JavaScript form submission → public/ajax-submit.php
   ↓
2. Rate limiting check (session-based)
   ↓
3. Input validation
   ↓
4. Database insertion
   ↓
5. AI provider selection
   ↓
6. Generate AI response
   ↓
7. Cache response
   ↓
8. Return JSON response
```

### Admin Request Flow

```
1. Request → admin/*.php
   ↓
2. Bootstrap application
   ↓
3. Guard authentication check
   ↓
4. Session timeout validation
   ↓
5. CSRF token validation (POST)
   ↓
6. Process request
   ↓
7. Update database
   ↓
8. Redirect or JSON response
```

## Database Schema

### Core Tables

#### submissions
```sql
CREATE TABLE submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    status ENUM('pending', 'replied', 'closed') DEFAULT 'pending',
    ai_reply TEXT DEFAULT NULL,
    admin_reply TEXT DEFAULT NULL,
    ref VARCHAR(32) UNIQUE NOT NULL,
    ticket_id VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ref (ref),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### settings
```sql
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### categories
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    keywords TEXT,
    priority INT DEFAULT 0,
    auto_reply_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### response_cache
```sql
CREATE TABLE response_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cache_key VARCHAR(64) UNIQUE NOT NULL,
    provider VARCHAR(20) NOT NULL,
    prompt_hash VARCHAR(64) NOT NULL,
    response TEXT NOT NULL,
    tokens_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    INDEX idx_key (cache_key),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Database Optimization

```sql
-- Optimize frequently queried tables
OPTIMIZE TABLE submissions;
ANALYZE TABLE submissions;

-- Add composite indexes for common queries
ALTER TABLE submissions 
ADD INDEX idx_status_created (status, created_at);

ALTER TABLE submissions 
ADD INDEX idx_email_status (email, status);

-- Partition large tables by date
ALTER TABLE submissions 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## Security Architecture

### Authentication Flow

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Browser    │────▶│  guard.php   │────▶│   Session    │
└──────────────┘     └──────────────┘     └──────────────┘
                            │                      │
                            ▼                      ▼
                     ┌──────────────┐     ┌──────────────┐
                     │ Check Token  │     │ Check Timeout│
                     └──────────────┘     └──────────────┘
                            │                      │
                            ▼                      ▼
                     ┌──────────────┐     ┌──────────────┐
                     │   Validate   │     │   Refresh    │
                     └──────────────┘     └──────────────┘
```

### CSRF Protection

```php
// Token generation
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Token validation
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

### Input Sanitization

```php
class InputSanitizer {
    public static function sanitize($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    public static function validate($input, $type) {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            default:
                return !empty($input);
        }
    }
}
```

## AI Integration Layer

### Provider Architecture

```
┌─────────────────────────────────────────┐
│            AI Controller                │
├─────────────────────────────────────────┤
│         Provider Factory                │
├─────────────┬─────────────┬─────────────┤
│   OpenAI    │   Claude    │   Gemini    │
│   Provider  │   Provider  │   Provider  │
├─────────────┴─────────────┴─────────────┤
│         HTTP Client Layer               │
├─────────────────────────────────────────┤
│         Response Parser                 │
├─────────────────────────────────────────┤
│         Cache Layer                     │
└─────────────────────────────────────────┘
```

### Request Flow

```php
class AIController {
    private $provider;
    private $cache;
    
    public function __construct(string $providerName) {
        $this->provider = AIProviderFactory::create($providerName);
        $this->cache = new ResponseCache();
    }
    
    public function generateReply(string $message, array $context): string {
        // Check cache first
        $cacheKey = $this->generateCacheKey($message, $context);
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        // Generate new response
        try {
            $response = $this->provider->generateReply($message, $context);
            $this->cache->set($cacheKey, $response, 3600); // 1 hour cache
            return $response;
        } catch (Exception $e) {
            // Fallback to another provider
            return $this->fallbackProvider($message, $context);
        }
    }
}
```

### Rate Limiting

```php
class RateLimiter {
    private $storage;
    
    public function check(string $identifier, int $limit, int $window): bool {
        $key = "rate_limit:$identifier";
        $current = time();
        $windowStart = $current - $window;
        
        // Get recent requests
        $requests = $this->storage->get($key, []);
        
        // Filter old requests
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check limit
        if (count($requests) >= $limit) {
            return false;
        }
        
        // Add current request
        $requests[] = $current;
        $this->storage->set($key, $requests, $window);
        
        return true;
    }
}
```

## Session Management

### Session Configuration

```php
class SessionManager {
    const TIMEOUT = 1800; // 30 minutes
    const REGENERATE_INTERVAL = 300; // 5 minutes
    
    public static function start() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', self::TIMEOUT);
        
        session_start();
        
        // Timeout check
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::TIMEOUT) {
                self::destroy();
                return false;
            }
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regenerate'])) {
            $_SESSION['last_regenerate'] = time();
        } elseif (time() - $_SESSION['last_regenerate'] > self::REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['last_regenerate'] = time();
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function destroy() {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
}
```

### Session Storage

```php
// Custom session handler for scalability
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;
    
    public function open($path, $name): bool {
        $this->db = Database::getInstance()->getConnection();
        return true;
    }
    
    public function read($id): string {
        $stmt = $this->db->prepare(
            "SELECT data FROM sessions WHERE id = :id AND expires > :now"
        );
        $stmt->execute(['id' => $id, 'now' => time()]);
        $result = $stmt->fetchColumn();
        return $result ?: '';
    }
    
    public function write($id, $data): bool {
        $expires = time() + SessionManager::TIMEOUT;
        $stmt = $this->db->prepare(
            "REPLACE INTO sessions (id, data, expires) VALUES (:id, :data, :expires)"
        );
        return $stmt->execute(['id' => $id, 'data' => $data, 'expires' => $expires]);
    }
    
    public function destroy($id): bool {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    public function gc($maxlifetime): int {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE expires < :now");
        $stmt->execute(['now' => time()]);
        return $stmt->rowCount();
    }
    
    public function close(): bool {
        return true;
    }
}
```

## Error Handling

### Global Error Handler

```php
class ErrorHandler {
    public static function register() {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    
    public static function handleException(Throwable $e) {
        $error = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        
        // Log error
        error_log(json_encode($error), 3, 'storage/logs/error.log');
        
        // Display user-friendly error
        if (getenv('APP_DEBUG') === 'true') {
            self::displayDebugError($error);
        } else {
            self::displayProductionError();
        }
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
```

### Application-Specific Exceptions

```php
class ValidationException extends Exception {
    private $field;
    
    public function __construct($message, $field = null) {
        parent::__construct($message);
        $this->field = $field;
    }
    
    public function getField() {
        return $this->field;
    }
}

class RateLimitException extends Exception {
    private $retryAfter;
    
    public function __construct($retryAfter = 60) {
        parent::__construct("Rate limit exceeded");
        $this->retryAfter = $retryAfter;
    }
    
    public function getRetryAfter() {
        return $this->retryAfter;
    }
}

class AIProviderException extends Exception {
    private $provider;
    
    public function __construct($message, $provider) {
        parent::__construct($message);
        $this->provider = $provider;
    }
    
    public function getProvider() {
        return $this->provider;
    }
}
```

## Performance Considerations

### Caching Strategy

```php
class CacheManager {
    private $strategies = [];
    
    public function __construct() {
        // Register cache strategies
        $this->strategies['file'] = new FileCacheStrategy();
        $this->strategies['database'] = new DatabaseCacheStrategy();
        $this->strategies['memory'] = new MemoryCacheStrategy();
    }
    
    public function get($key, $strategy = 'file') {
        return $this->strategies[$strategy]->get($key);
    }
    
    public function set($key, $value, $ttl = 3600, $strategy = 'file') {
        return $this->strategies[$strategy]->set($key, $value, $ttl);
    }
    
    public function invalidate($pattern = '*') {
        foreach ($this->strategies as $strategy) {
            $strategy->invalidate($pattern);
        }
    }
}
```

### Query Optimization

```php
class QueryOptimizer {
    public static function explainQuery($sql, $params = []) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("EXPLAIN " . $sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public static function analyzeSlowQueries($threshold = 0.1) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT query_time, sql_text 
            FROM mysql.slow_log 
            WHERE query_time > $threshold
            ORDER BY query_time DESC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }
}
```

### Resource Management

```php
class ResourceManager {
    private static $resources = [];
    
    public static function register($name, $resource) {
        self::$resources[$name] = $resource;
    }
    
    public static function cleanup() {
        foreach (self::$resources as $name => $resource) {
            if ($resource instanceof PDO) {
                $resource = null;
            } elseif (is_resource($resource)) {
                fclose($resource);
            }
        }
        self::$resources = [];
    }
    
    public static function __destruct() {
        self::cleanup();
    }
}

// Register cleanup
register_shutdown_function([ResourceManager::class, 'cleanup']);
```

### Load Balancing Considerations

```php
// Health check endpoint
class HealthCheck {
    public static function check(): array {
        $checks = [];
        
        // Database check
        try {
            $db = Database::getInstance()->getConnection();
            $db->query("SELECT 1");
            $checks['database'] = 'ok';
        } catch (Exception $e) {
            $checks['database'] = 'fail';
        }
        
        // File system check
        $checks['storage_writable'] = is_writable('storage/');
        
        // Session check
        $checks['session'] = session_status() === PHP_SESSION_ACTIVE;
        
        // Memory check
        $checks['memory_usage'] = memory_get_usage(true);
        $checks['memory_limit'] = ini_get('memory_limit');
        
        return $checks;
    }
}
```

## Deployment Architecture

### Production Environment

```
┌─────────────────┐
│   Load Balancer │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌──────┐  ┌──────┐
│ Web1 │  │ Web2 │
└──┬───┘  └───┬──┘
   │          │
   └────┬─────┘
        ▼
   ┌─────────┐
   │   CDN   │
   └─────────┘
        │
   ┌────┴────┐
   ▼         ▼
┌──────┐  ┌──────┐
│MySQL │  │Redis │
│Master│  │Cache │
└──┬───┘  └──────┘
   │
   ▼
┌──────┐
│MySQL │
│Slave │
└──────┘
```

### Scaling Strategies

1. **Horizontal Scaling**: Add more web servers behind load balancer
2. **Database Replication**: Master-slave configuration for read scaling
3. **Caching Layer**: Redis/Memcached for session and response caching
4. **CDN Integration**: Static assets served from CDN
5. **Queue System**: Background job processing for emails and AI requests
6. **Microservices**: Separate AI processing into dedicated service

---

**Version**: 1.0.0  
**Last Updated**: August 2025  
**Architecture Review**: Quarterly