# ReplyPilot AI - Mail Sending Audit

## Mail Send Paths

### Primary Send Locations
| Location | Purpose | Method | Error Handling |
|----------|---------|--------|----------------|
| public/index.php | Contact form submission | Mailer class | None |
| public/ajax-submit.php | AJAX submission | Mailer class | Try/catch with JSON response |
| admin/send_email.php | Admin manual send | Mailer class | Redirect with status |
| admin/update_reply.php | Update & send reply | Mailer class | Redirect with status |

## Mailer Implementation Analysis

### Mailer Classes
| Class | Location | Purpose | Issues |
|-------|----------|---------|--------|
| App\Support\Mailer | app/Support/Mailer.php | Production mailer | SMTP password env mismatch |
| App\Support\MailerMock | app/Support/MailerMock.php | Mock for testing | Not reviewed |

### PHPMailer Bootstrap Issues
| Issue | Risk | Fix |
|-------|------|-----|
| Manual require_once | Fatal if vendor structure changes | Add file_exists checks |
| No class_exists check after require | May fail silently | Verify class loaded |
| Hardcoded vendor paths | Brittle dependency | Use dynamic path resolution |

## Configuration Sources

### Environment Variables
| Variable | Used In | Issue | Fix |
|----------|---------|-------|-----|
| MAIL_FROM_ADDRESS | Mailer.php | No validation | Add email validation |
| MAIL_FROM_NAME | Mailer.php | No sanitization | Sanitize for headers |
| SMTP_HOST | Mailer.php | No default | Add fallback |
| SMTP_PORT | Mailer.php | String to int cast | Validate port range |
| SMTP_USERNAME | Mailer.php | Used as-is | OK |
| SMTP_PASSWORD | Mailer.php | Wrong env key (should be SMTP_PASS) | Fix env key |
| SMTP_ENCRYPTION | Mailer.php | Case sensitive | Normalize to lowercase |
| SMTP_AUTH | Mailer.php | String comparison | Use boolean cast |

### Admin Settings
| Setting | Source | Issue |
|---------|--------|-------|
| mail_transport | Settings | Not used in Mailer | Implement transport selection |
| mail_from_name | Settings | Not used | Read from Settings |
| mail_from_address | Settings | Not used | Read from Settings |

## Security Analysis

### Header Injection Prevention
| Location | Status | Issue | Fix |
|----------|--------|-------|-----|
| Subject sanitization | ✓ Implemented | str_replace \r\n | OK |
| From name sanitization | ✓ Implemented | str_replace \r\n | OK |
| Email validation | ✓ Basic | Only FILTER_VALIDATE_EMAIL | Add MX check |

### Address Validation
| Check | Status | Notes |
|-------|--------|-------|
| Format validation | ✓ Yes | Uses filter_var |
| MX record check | ❌ No | Should add for production |
| Disposable email check | ❌ No | Consider adding |
| Rate limiting | ❌ No | Add per-email rate limit |

## Reliability Issues

### Error Handling
| Component | Issue | Risk | Fix |
|-----------|-------|------|-----|
| SMTP connection | No timeout set | Hangs on slow network | Add $mail->Timeout = 10 |
| SMTP auth failure | Falls back to mail() | May fail silently | Log auth failures |
| mail() fallback | Basic error only | No detailed error | Capture mail() errors |
| No retry logic | Single attempt only | Transient failures lost | Add retry mechanism |

### Logging
| Event | Logged | Location | Issue |
|-------|--------|----------|-------|
| SMTP success | ❌ No | - | Add success logging |
| SMTP failure | ✓ Yes | error_log | Domain leaked in logs |
| mail() fallback | ✓ Yes | error_log | Domain leaked |
| Invalid email | ✓ Yes | error_log | OK |

## AJAX Email Handling

### ajax-submit.php Issues
| Component | Issue | Fix |
|-----------|-------|-----|
| Admin notification | Always sent if ADMIN_EMAIL set | Add setting to control |
| Admin email validation | Basic check only | Validate before sending |
| Response format | JSON with exit | OK |
| Error envelope | Proper structure | OK |

## Email Content Issues

### Template/Content
| Issue | Location | Risk | Fix |
|-------|----------|------|-----|
| No HTML template | All locations | Plain text only | Add HTML templates |
| No text alternative | Mailer.php | HTML only sent | Add multipart support |
| No personalization | All sends | Generic content | Add template variables |
| No unsubscribe | All emails | Compliance issue | Add unsubscribe link |

## Compliance & Best Practices

### Missing Features
| Feature | Impact | Priority |
|---------|--------|----------|
| SPF/DKIM setup | Deliverability | High |
| Bounce handling | List hygiene | Medium |
| Complaint handling | Reputation | Medium |
| Email queue | Performance | Low |
| Delivery tracking | Analytics | Low |

## Critical Issues Summary

### Must Fix
1. **SMTP_PASSWORD vs SMTP_PASS** - Environment variable mismatch
2. **No timeout on SMTP** - Can hang indefinitely
3. **No rate limiting** - Email abuse possible
4. **mail_transport setting ignored** - Settings not used

### Should Fix
1. **Admin email always sent** - Add control setting
2. **Domain in error logs** - Information leak
3. **No MX validation** - Invalid emails attempted
4. **No retry logic** - Transient failures lost

### Nice to Have
1. **HTML templates** - Better formatting
2. **Email queue** - Better performance
3. **Bounce handling** - List maintenance
4. **Analytics** - Track open/click rates

## Files to Edit

### Critical Priority
1. **app/Support/Mailer.php**
   - Fix SMTP_PASSWORD to SMTP_PASS
   - Add timeout setting
   - Add file_exists checks for PHPMailer
   - Use Settings for from address/name

2. **public/ajax-submit.php**
   - Add admin notification setting check
   - Improve admin email validation

3. **admin/send_email.php**
   - Add rate limiting
   - Add MX record validation

### Medium Priority
1. **app/Repository/EmailRepository.php**
   - Add more detailed logging
   - Track delivery status

2. **admin/update_reply.php**
   - Add email validation
   - Add rate limiting

## Recommendations

### Immediate Actions
1. Fix SMTP_PASSWORD environment variable
2. Add SMTP timeout (10 seconds)
3. Implement rate limiting (max 10 emails/minute)
4. Add file_exists checks for PHPMailer includes

### Configuration Improvements
1. Use Settings instead of only ENV for mail config
2. Add mail_transport selection support
3. Add admin notification control setting
4. Document SMTP setup requirements

### Security Enhancements
1. Add MX record validation
2. Implement per-recipient rate limiting
3. Sanitize all email headers properly
4. Add email whitelist/blacklist option

### Reliability Improvements
1. Add retry logic (3 attempts)
2. Implement email queue
3. Add health check for SMTP
4. Better error messages and logging