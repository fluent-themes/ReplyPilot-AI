# ReplyPilot AI - Laragon Local Deployment Audit

## Laragon Environment Analysis

### Potential Issues When Running on Laragon

## 1. Session Configuration

### Issues
| Component | Problem | Impact | Temporary Fix |
|-----------|---------|--------|---------------|
| Session save path | May use system temp | Sessions lost on restart | Set custom session.save_path |
| Session cookie domain | localhost vs 127.0.0.1 | Session not shared | Use consistent domain |
| Session cookie secure | HTTPS flag may be set | Cookies not sent on HTTP | Disable secure flag locally |

### Recommended Fixes
```php
// Add to bootstrap.php for Laragon
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.save_path', __DIR__ . '/storage/sessions');
}
```

## 2. Database Connection

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| DB_HOST | May need 127.0.0.1 | Connection fails with localhost | Use 127.0.0.1 |
| MySQL port | Laragon may use custom port | Connection fails | Check Laragon MySQL port |
| Socket connection | Windows socket path differs | Connection timeout | Use TCP/IP not socket |

### Recommended .env Settings
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_CONNECTION=mysql
```

## 3. File Paths & Permissions

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| Directory separators | Mixed / and \ | Include failures | Use DIRECTORY_SEPARATOR |
| Case sensitivity | Windows case-insensitive | Works locally, fails on Linux | Verify exact case |
| Write permissions | Windows permissions different | Cannot write logs/cache | Ensure storage/ writable |
| Temp directory | Windows temp path | Temp files in wrong location | Set explicit temp path |

## 4. Email Configuration

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| mail() function | May not work on Windows | Emails fail | Use SMTP always |
| Sendmail path | Not configured in Laragon | mail() fails | Configure sendmail |
| SMTP | May need local mail catcher | No email testing | Use MailHog/MailCatcher |

### Recommended Local Email Setup
```
MAIL_TRANSPORT=smtp
SMTP_HOST=127.0.0.1
SMTP_PORT=1025
SMTP_AUTH=false
# Use MailHog with Laragon
```

## 5. URL & Routing Issues

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| Base URL | May include port number | Broken links | Detect and handle port |
| Pretty URLs | .htaccess may not work | Routing fails | Ensure mod_rewrite enabled |
| HTTPS detection | $_SERVER['HTTPS'] unreliable | Wrong protocol detected | Check multiple indicators |
| Virtual hosts | Laragon auto-virtual hosts | URL mismatch | Configure proper vhost |

### URL Detection Fix
```php
// Better HTTPS detection for Laragon
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || $_SERVER['SERVER_PORT'] == 443
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
```

## 6. PHP Configuration

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| Error display | May be on by default | Errors shown to users | Set display_errors = 0 |
| Memory limit | May be low | Script fails | Increase memory_limit |
| Max execution time | May be too short | Timeout on install | Increase max_execution_time |
| Upload limits | May be restrictive | Cannot upload files | Increase upload limits |

### Recommended php.ini Settings
```ini
display_errors = Off
error_reporting = E_ALL
log_errors = On
memory_limit = 256M
max_execution_time = 300
post_max_size = 20M
upload_max_filesize = 20M
```

## 7. Composer & Autoloading

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| Composer path | May not be in PATH | Cannot run composer | Add to Windows PATH |
| Autoload cache | May be stale | Classes not found | Run composer dump-autoload |
| Vendor binaries | Windows .bat files | Scripts fail | Use proper binary path |

## 8. AJAX & CORS

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| CORS | Different ports = different origin | AJAX blocked | Add CORS headers |
| Session cookies | SameSite issues | Session lost on AJAX | Configure SameSite=Lax |

### CORS Fix for Development
```php
// Add to ajax-submit.php for local dev
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
```

## 9. Installation Process

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| Token in URL | Browser may cache | Security risk | Clear after use |
| Database creation | User may lack CREATE privilege | Install fails | Pre-create database |
| Table creation | Timeout on slow system | Partial install | Increase timeout |

## 10. Caching Issues

### Issues
| Component | Problem | Impact | Fix |
|-----------|---------|--------|-----|
| Browser cache | Aggressive caching | Changes not visible | Add cache busters |
| OPcache | May cache old code | Changes not reflected | Reset OPcache |
| File cache | Windows file locks | Cannot clear cache | Use different cache driver |

## Laragon-Specific Configuration File

Create `laragon.config.php`:
```php
<?php
// Laragon-specific overrides
if (isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '.test') !== false
)) {
    // Session configuration
    ini_set('session.cookie_secure', '0');
    ini_set('session.save_path', __DIR__ . '/storage/sessions');
    
    // Error reporting for development
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    
    // Timezone
    date_default_timezone_set('America/New_York');
    
    // Memory and execution limits
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', '300');
    
    // Define development mode
    define('LARAGON_ENV', true);
    define('APP_DEBUG', true);
}
```

## Setup Instructions for Laragon

### Step 1: Create Database
```sql
CREATE DATABASE replypilot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'replypilot'@'127.0.0.1' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON replypilot.* TO 'replypilot'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### Step 2: Configure .env
```
APP_ENV=local
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=replypilot
DB_USER=root
DB_PASS=
MAIL_TRANSPORT=smtp
SMTP_HOST=127.0.0.1
SMTP_PORT=1025
SMTP_AUTH=false
```

### Step 3: Create Required Directories
```
mkdir storage/sessions
mkdir storage/logs
mkdir storage/cache
mkdir storage/mail
```

### Step 4: Set Permissions (Windows)
- Right-click storage folder → Properties
- Security tab → Edit → Add "Everyone" → Full Control
- Apply to all subfolders

### Step 5: Enable Required PHP Extensions
Ensure these are enabled in Laragon's PHP:
- pdo_mysql
- openssl
- mbstring
- json
- curl

## Summary of Required Changes

### Critical for Laragon
1. **Session save path** - Set custom path in storage/
2. **Database host** - Use 127.0.0.1 not localhost
3. **Email configuration** - Use SMTP not mail()
4. **File permissions** - Ensure storage/ is writable

### Recommended for Development
1. Add Laragon detection in bootstrap.php
2. Create laragon.config.php for overrides
3. Use MailHog for email testing
4. Add development-specific error handling

### Files to Modify
1. **bootstrap.php** - Add Laragon detection
2. **.env.example** - Add Laragon-specific example
3. **public/.htaccess** - Ensure works with Laragon
4. **installer.php** - Add Laragon-specific hints

## Testing Checklist

- [ ] Installation completes without errors
- [ ] Sessions persist across requests
- [ ] Database connections work
- [ ] Email sending (via MailHog)
- [ ] AJAX requests work
- [ ] File uploads work (if implemented)
- [ ] Admin panel accessible
- [ ] No permission errors in logs
- [ ] URLs resolve correctly
- [ ] Static assets load properly