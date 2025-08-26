# ReplyPilot AI - Installer Flow Audit

## Entry Point Analysis

### Routing to Installer
- **Route**: `/?page=install&token=token`
- **Handler**: `public/installer.php`
- **Token Default**: `setup123` (defined in `INSTALL_FALLBACK_TOKEN`)
- **Bootstrap**: Requires `bootstrap.php` which loads env and autoloader

### Critical Issues Found

## 1. Bootstrap Sequence Issues

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| Autoloader order | bootstrap.php | Fatal if vendor missing and Env used before fallback autoloader | Move Env::load() after autoloader setup |
| Dotenv dependency | app/Core/Env.php | Fatal error if vendor/autoload missing | Add class_exists check for Dotenv |
| Include order | public/installer.php | Bootstrap included after token constant defined | Move constant definition after bootstrap |

## 2. Token Handling

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| Token visible in error | app/Installer/Installer.php | Security leak on error pages | Remove token from error messages |
| Fallback token hardcoded | public/installer.php | Security risk if not changed | Document requirement to change |
| Token logged | app/Installer/Installer.php:logLine() | Token visible in logs | Mask token in logs |

## 3. Database Creation

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| No charset in initial DSN | app/Installer/Installer.php | Encoding issues | Add charset=utf8mb4 to DSN |
| No error mode set | app/Installer/Installer.php | Silent failures | Add ERRMODE_EXCEPTION |
| Transaction without check | app/Installer/Installer.php | May fail if no transaction support | Check inTransaction() before rollback |

## 4. File Operations

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| No parent dir check | app/Installer/EnvWriter.php | Write fails if directory missing | Check and create parent directory |
| Temp file not unique enough | app/Installer/EnvWriter.php | Collision risk | Use more entropy in temp filename |
| No permission check | app/Installer/Installer.php:logLine() | Silent log failure | Check is_writable before logging |

## 5. Session Management

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| Session regenerate without check | app/Installer/Installer.php | Warning if no session | Check session_status() first |
| No session timeout | app/Installer/Installer.php | Session persists indefinitely | Add session timeout |
| Admin unlock too broad | app/Installer/Installer.php | Grants full admin access | Limit scope of unlock |

## 6. Error Handling

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| HTML in POST response | app/Installer/Installer.php | No JSON error option | Add Accept header check |
| Credentials in error logs | app/Support/Database.php | Security leak | Sanitize DB errors |
| Stack trace exposed | app/Installer/Installer.php | Information disclosure | Limit error details in production |

## 7. Linux Compatibility

| Issue | Location | Risk | Fix Required |
|-------|----------|------|--------------|
| Forward slashes in require | app/Installer/Installer.php | May fail on Windows | Use DIRECTORY_SEPARATOR |
| Case sensitivity not checked | All files | Include fails on Linux | Verify exact case of filenames |
| Line endings mixed | Various files | Git issues | Standardize to LF |

## Installation Flow Summary

1. **Entry**: User visits `/?page=install&token=setup123`
2. **Token Check**: Validates token against .env or fallback
3. **Session**: Regenerates session ID and sets admin unlock
4. **Form Display**: Shows database config form
5. **POST Processing**:
   - Validates inputs
   - Creates .env file
   - Tests database connection
   - Creates database if needed
   - Creates tables
   - Shows success or error

## Recommended Fixes Priority

### Critical (Blocks Installation)
1. Fix autoloader ordering in bootstrap.php
2. Add Dotenv class existence check
3. Fix database charset in DSN
4. Add proper error handling for file operations

### High (Security/Stability)
1. Remove token from error messages
2. Add session status checks
3. Sanitize database error messages
4. Add transaction state checks

### Medium (Compatibility)
1. Use DIRECTORY_SEPARATOR consistently
2. Standardize line endings
3. Add more detailed error logging
4. Improve temp file uniqueness

## Files to Edit

1. **bootstrap.php** - Fix autoloader order
2. **app/Core/Env.php** - Add Dotenv class check
3. **app/Installer/Installer.php** - Multiple fixes (token, session, DB)
4. **app/Installer/EnvWriter.php** - Directory and permission checks
5. **public/installer.php** - Move constant definition

## Post-Installation Verification

The installer should:
- ✅ Create .env file with correct permissions
- ✅ Create database with utf8mb4 charset
- ✅ Create all 6 required tables
- ✅ Set session for admin access
- ✅ Redirect to admin panel
- ❌ Currently missing: Verification that tables were created
- ❌ Currently missing: Rollback on partial failure