# Security Policy

## Supported Versions

Currently supported versions for security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of ReplyPilot AI seriously. If you discover a security vulnerability, please follow these steps:

### DO NOT:
- Create a public GitHub issue
- Share vulnerability details in public forums
- Include personally identifiable information (PII) in reports
- Include actual logs containing sensitive data

### DO:
1. **Email us directly**: Send vulnerability reports to support@fluentthemes.com
2. **Include details**:
   - Type of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)
3. **Allow time**: We aim to respond within 48 hours

## Security Features

ReplyPilot AI implements multiple security layers:

- **CSRF Protection**: All forms include token validation
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Input sanitization and output escaping
- **Session Security**: Secure session handling with timeout
- **Access Control**: Admin authentication middleware
- **Rate Limiting**: Built-in API endpoint protection

## Best Practices

1. **Change default tokens**: Always update the installer token after setup
2. **Use strong passwords**: Enforce complex admin passwords
3. **Keep updated**: Regularly update PHP and dependencies
4. **Enable HTTPS**: Always use SSL/TLS in production
5. **Review logs**: Monitor access and error logs regularly
6. **Restrict access**: Limit admin panel access by IP when possible

## Security Headers

Recommended security headers for production:

```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

## Contact

Security Contact: support@fluentthemes.com
Response Time: 24-48 hours
