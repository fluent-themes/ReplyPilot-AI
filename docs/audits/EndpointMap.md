# ReplyPilot AI - Complete Request Path Mapping

## Public Endpoints

| Client Trigger | URL | Method | Server Handler | Required Params | Optional Params | Session/CSRF | Response | Notes |
|----------------|-----|--------|----------------|-----------------|-----------------|--------------|----------|-------|
| Form: public/index.php (main contact form) | /public/index.php | POST | public/index.php | name, email, message | tone, purchase_code, product_name | No CSRF | HTML (redirect to thank-you.php) | Main contact form submission |
| JS: None | /public/ajax-submit.php | POST | public/ajax-submit.php | name, email, message | tone, purchase_code, product_name | Session (rate limit) | JSON | AJAX form submission with rate limiting |
| Link: public/index.php | /?page=install&token={token} | GET | public/installer.php | token | - | Session | HTML | Installer page |
| Form: public/installer.php | /?page=install&token={token} | POST | public/installer.php | db_host, db_name, db_user | db_pass, openai_key, smtp_*, envato_token | Session | HTML | Installation process |
| Link: thank-you.php, index.php | /?page=ticket&ref={ref} | GET | public/ticket.php | ref | - | Session (ticket access) | HTML | View ticket details |
| Direct: thank-you.php | /public/thank-you.php | GET | public/thank-you.php | - | ref | No | HTML | Thank you page after submission |

## Admin Endpoints

| Client Trigger | URL | Method | Server Handler | Required Params | Optional Params | Session/CSRF | Response | Notes |
|----------------|-----|--------|----------------|-----------------|-----------------|--------------|----------|-------|
| Direct: Various | /admin/ | GET | admin/index.php | - | - | Session (guard) | HTML | Admin dashboard |
| Form: admin/settings.php | /admin/update_settings.php | POST | admin/update_settings.php | csrf_token | purchase_validation_enabled, purchase_code_enabled, purchase_code_required | Session + CSRF | Redirect | Update basic settings |
| Form: admin/index.php | /admin/update_reply.php | POST | admin/update_reply.php | id, _csrf | ai_reply, category, send, to, subject, body | Session + CSRF | Redirect | Update submission reply |
| Form: admin/send_email.php | /admin/send_email.php | POST | admin/send_email.php | _csrf, to, subject, body | id | Session + CSRF | Redirect | Send email to user |
| JS: admin/advanced_settings.php | /admin/test_provider.php | GET | admin/test_provider.php | type, provider | - | Session (guard) | JSON | Test AI/License provider connection |
| Direct: admin/index.php | /admin/export_csv.php | GET | admin/export_csv.php | - | - | Session (guard) | CSV file download | Export submissions to CSV |
| Form: admin/advanced_settings.php | /admin/update_advanced_settings.php | POST | admin/update_advanced_settings.php | csrf_token | ai_provider, license_validator, various settings | Session + CSRF | Redirect | Update advanced settings |
| Direct: Various | /admin/envato.php | GET | admin/envato.php | - | - | Session (guard) | HTML | Envato settings page |
| Direct: Various | /admin/categories.php | GET | admin/categories.php | - | - | Session (guard) | HTML | Category management page |
| Direct: Various | /admin/advanced_settings.php | GET | admin/advanced_settings.php | - | - | Session (guard) | HTML | Advanced settings page |
| Direct: Various | /admin/system_health.php | GET | admin/system_health.php | - | - | Session (guard) | HTML | System health monitoring |
| Direct: Analytics | /admin/analytics.php | GET | admin/analytics.php | - | - | Session (guard) | HTML | Analytics dashboard (Placeholder) |
| Direct: Analytics | /admin/export_analytics.php | GET | admin/export_analytics.php | - | - | Session (guard) | HTML/CSV | Export analytics (Placeholder) |
| Direct: Analytics | /admin/clear_analytics.php | GET/POST | admin/clear_analytics.php | - | - | Session (guard) | Redirect | Clear analytics data (Placeholder) |
| Direct: Cache | /admin/manage_cache.php | GET/POST | admin/manage_cache.php | - | - | Session (guard) | HTML | Manage cache (Placeholder) |

## Guard/Auth Mechanism

| Entry Point | Auth Method | Session Keys | Protection |
|-------------|-------------|--------------|------------|
| admin/guard.php | Session-based with token unlock | rpai_admin_unlocked | Requires one-time token to unlock admin session |
| public/installer.php | Token-based | rpai_admin_unlocked | Requires INSTALL_TOKEN from .env or fallback |

## API/AJAX Endpoints Summary

| Endpoint | Rate Limiting | Error Handling | Security |
|----------|---------------|----------------|----------|
| /public/ajax-submit.php | 6 requests/60s (session-based) | JSON error responses | Input validation, sanitization |
| /admin/test_provider.php | None | JSON error responses | Session guard |

## Session/CSRF Token Usage

| Location | Token Name | Generation | Validation |
|----------|------------|------------|------------|
| admin/update_settings.php | csrf_token | $_SESSION['csrf_token'] | hash_equals() |
| admin/update_reply.php | _csrf | $_SESSION['csrf_token'] | hash_equals() |
| admin/send_email.php | _csrf | $_SESSION['csrf_token'] | hash_equals() |
| admin/update_advanced_settings.php | csrf_token | $_SESSION['csrf_token'] | hash_equals() |

## Total Endpoints: 23
## Mapped Endpoints: 23