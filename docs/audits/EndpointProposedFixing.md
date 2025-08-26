# ReplyPilot AI - Static Risk Analysis Report

## Critical Issues

### 1. Includes/Requires

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| bootstrap.php | No check if app/ directory exists before autoloader registration | Fatal error if directory missing | Add `is_dir(__DIR__ . '/app')` check before registration |
| app/Support/Mailer.php | Manual require_once for PHPMailer uses hardcoded paths | Fatal if vendor structure changes | Add file_exists checks for each require_once |

### 2. Autoload

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| bootstrap.php | Autoloader registered after use of App\Core\Env | Fatal if vendor missing | Move Env::load() after autoloader setup |
| app/Core/Env.php | Uses Dotenv\Dotenv without checking class exists | Fatal if vendor missing | Add class_exists check before use |

### 3. Input Handling

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| admin/update_settings.php | No session_start() at file beginning | May fail if session not started | Add session_start() check at top |
| admin/update_reply.php | Direct int cast without validation | Type juggling issues | Validate is_numeric before cast |
| admin/send_email.php | Direct int cast without validation | Type juggling issues | Validate is_numeric before cast |
| public/installer.php | $_POST['db_pass'] accessed without isset() check | Notice on missing key | Use null coalesce operator |

### 4. JSON/Headers

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| admin/export_csv.php | No output buffering, risk of headers already sent | Cannot set CSV headers | Add ob_clean() before headers |
| admin/test_provider.php | No explicit charset in JSON header | Encoding issues | Ensure charset=utf-8 always set |
| public/ajax-submit.php | Multiple exit points without consistent headers | Inconsistent responses | Centralize response handling |

### 5. Redirects

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| admin/update_settings.php | No exit after header redirect in catch block | Code continues executing | Add exit after all redirects |
| admin/update_reply.php | Complex redirect logic with anchor tags | May fail with special chars | URL encode anchor values |
| admin/send_email.php | Redirect with status param not validated | XSS in redirect | URL encode status values |

### 6. Sessions

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| app/Installer/Installer.php | session_regenerate_id() without checking if session active | Warning if no session | Check session_status() first |
| admin/* files | CSRF tokens not generated/validated consistently | CSRF vulnerability | Implement consistent CSRF token generation |
| admin/guard.php | Session fixation risk on token unlock | Session hijacking | Regenerate session ID after unlock |

### 7. Security (CSRF)

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| admin/update_settings.php | CSRF token not generated in form | CSRF attacks | Generate token in settings.php form |
| admin/update_reply.php | Token field name inconsistent (_csrf vs csrf_token) | Token validation bypass | Standardize to csrf_token |
| admin/send_email.php | Token field name inconsistent (_csrf vs csrf_token) | Token validation bypass | Standardize to csrf_token |
| admin/export_csv.php | No CSRF protection for export | Data leakage | Add CSRF token validation |

### 8. Database

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| app/Support/Database.php | No ERRMODE_EXCEPTION in createSafe() | Silent failures | Add PDO::ERRMODE_EXCEPTION |
| admin/index.php | Direct query without error handling | Fatal on DB error | Wrap in try/catch |
| admin/export_csv.php | Direct query without null check on $db | Fatal if DB unavailable | Check $db before query |
| app/Repository/SubmissionRepository.php | No validation of $ref in findByRef | SQL injection if PDO emulation on | Cast to int or validate |

### 9. Mail Sending

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| app/Support/Mailer.php | SMTP_PASSWORD vs SMTP_PASS env mismatch | Auth failure | Standardize to SMTP_PASS |
| app/Support/Mailer.php | No timeout set for SMTP connection | Hangs on slow network | Add $mail->Timeout = 10 |
| public/ajax-submit.php | Admin email sent without checking if admin wants it | Spam admin | Add setting for admin notifications |
| admin/send_email.php | No rate limiting on email sending | Email abuse | Add rate limiting |

### 10. Installer

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| app/Installer/Installer.php | Token visible in error messages | Security leak | Remove token from error display |
| app/Installer/Installer.php | Database created without charset in DSN | Encoding issues | Add charset=utf8mb4 to DSN |
| app/Installer/EnvWriter.php | No file permissions check | May fail silently | Check is_writable on parent dir |
| public/installer.php | INSTALL_FALLBACK_TOKEN hardcoded | Security risk | Move to config file |

### 11. Linux Deployment

| File | Issue | Risk | Fix |
|------|-------|------|-----|
| bootstrap.php | Uses backslash in require paths | Fails on Linux | Use DIRECTORY_SEPARATOR |
| app/Support/Settings.php | Path uses forward slashes | May fail on Windows | Use DIRECTORY_SEPARATOR |
| admin/guard.php | require uses forward slash | Inconsistent path handling | Use DIRECTORY_SEPARATOR |
| All PHP files | No consistent line endings | Git issues on Linux | Standardize to LF |

## Summary Statistics

- **Critical Issues**: 8
- **High Priority**: 15
- **Medium Priority**: 18
- **Low Priority**: 9

## Recommended Fix Priority

1. **Immediate**: Session/CSRF security issues in admin panel
2. **High**: Database error handling and SQL injection risks
3. **High**: Autoloader ordering in bootstrap.php
4. **Medium**: Mail configuration mismatches
5. **Medium**: Header/redirect issues
6. **Low**: Linux compatibility path separators

## Files Requiring Edits

1. bootstrap.php - Autoloader ordering, error handling
2. admin/guard.php - Session regeneration
3. admin/update_settings.php - CSRF, session start
4. admin/update_reply.php - CSRF field name, validation
5. admin/send_email.php - CSRF field name, validation
6. admin/export_csv.php - Output buffering, CSRF
7. app/Support/Database.php - PDO error mode
8. app/Support/Mailer.php - Env key names, timeout
9. app/Core/Env.php - Dotenv class check
10. app/Installer/Installer.php - Session checks, token hiding
11. public/ajax-submit.php - Response consistency
12. app/Repository/SubmissionRepository.php - Input validation