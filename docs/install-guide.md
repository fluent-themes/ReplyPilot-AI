# Installation Guide

For complete installation instructions, please refer to [INSTALL.md](../INSTALL.md) in the project root.

This guide provides the same comprehensive installation instructions for ReplyPilot AI v6.

## Quick Start

### Requirements
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ with mod_rewrite
- Required PHP extensions: PDO, cURL, JSON, Session, OpenSSL, Mbstring

### Web Installer (Easiest)

1. Extract files to web server
2. Set permissions: `chmod 755 storage/`
3. Navigate to: `https://yourdomain.com/?page=install&token=setup123`
4. Follow the wizard
5. **Important**: Change default installer token after setup

### Manual Installation

1. Clone repository
2. Create database and user
3. Copy `.env.example` to `.env` and configure
4. Run migrations: `php scripts/auto_migrate.php`
5. Set directory permissions

### Platform-Specific

- **Linux**: Standard LAMP stack setup
- **Windows**: Use Laragon with `.env.LaragonExample`
- **Docker**: Coming soon

## Post-Installation

1. Verify installation at `/`
2. Access admin panel at `/admin/`
3. Configure AI provider settings
4. Test email functionality
5. Review security settings

## Troubleshooting

- **500 Error**: Check `.htaccess` and PHP version
- **Database Error**: Verify credentials in `.env`
- **Email Issues**: Check SMTP settings and firewall

## Support

- Documentation: `/docs/` directory
- Debug Guide: [DEBUG.md](DEBUG.md)
- Email: support@fluentthemes.com

---

[← Back to Documentation](README.md) | [Admin Guide →](admin-guide.md)
