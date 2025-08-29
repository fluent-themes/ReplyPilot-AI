# Installation Guide

This guide covers the installation of ReplyPilot AI on various platforms.

## System Requirements

### Minimum Requirements

- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **PHP Extensions**: 
  - PDO with MySQL driver
  - cURL
  - JSON
  - Session
  - OpenSSL
  - Mbstring

### Recommended Specifications

- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 100MB for application + space for logs
- **PHP Memory Limit**: 128MB minimum
- **Max Execution Time**: 60 seconds

## Installation Methods

### Method 1: Web Installer (Recommended)

This is the easiest method for most users.

#### Step 1: Download and Extract

```bash
# Download the latest release
wget https://github.com/fluent-themes/replypilot-ai/archive/main.zip

# Extract to your web server directory
unzip main.zip -d /var/www/html/
cd /var/www/html/replypilot-ai
```

#### Step 2: Set Permissions

**Linux/Unix:**
```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/mail/
chown -R www-data:www-data storage/
```

**Windows (Laragon/XAMPP):**
Permissions are usually handled automatically. Ensure the web server user has write access to the `storage/` directory.

#### Step 3: Configure Web Server

**Apache Configuration:**
```apache
<Directory /var/www/html/replypilot-ai>
    AllowOverride All
    Require all granted
</Directory>
```

Enable mod_rewrite:
```bash
a2enmod rewrite
systemctl restart apache2
```

#### Step 4: Run the Installer

1. Open your browser and navigate to:
   ```
   https://yourdomain.com/?page=install&token=setup123
   ```

2. Follow the installation wizard:
   - Enter database credentials
   - Configure AI provider (OpenAI/Claude/Gemini)
   - Enter API keys
   - Set admin email and password
   - Configure email settings (SMTP)

#### Step 5: Secure Your Installation

**IMPORTANT**: After installation, immediately:

1. Change the default installer token:
   - Login to admin panel: `https://yourdomain.com/admin/`
   - Go to Settings → Advanced Settings
   - Update the installer token
   - Save changes

2. Remove installer access (optional):
   ```bash
   chmod 000 public/installer.php
   ```

### Method 2: Manual Installation

For advanced users who prefer manual setup.

#### Step 1: Clone Repository

```bash
git clone https://github.com/fluent-themes/replypilot-ai.git
cd replypilot-ai
```

#### Step 2: Create Database

```sql
CREATE DATABASE replypilot_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'replypilot_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON replypilot_db.* TO 'replypilot_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Step 3: Configure Environment

```bash
cp .env.example .env
nano .env
```

Edit the `.env` file with your settings:
```env
# Database
DB_HOST=localhost
DB_NAME=replypilot_db
DB_USER=replypilot_user
DB_PASS=your_password

# AI Provider (openai, claude, or gemini)
AI_PROVIDER=openai
OPENAI_API_KEY=your_api_key_here

# Email Settings
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_password
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME="ReplyPilot AI"

# Admin
ADMIN_EMAIL=admin@yourdomain.com
```

#### Step 4: Run Database Migrations

```bash
php scripts/auto_migrate.php
```

#### Step 5: Set Permissions

```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/mail/
```

## Post-Installation

### Verify Installation

1. Check the application loads without errors
2. Test form submission at `/`
3. Login to admin panel at `/admin/`
4. Send a test email from admin panel
5. Verify AI provider connection in settings

### Configure Cron Jobs (Optional)

For automated tasks, add to crontab:
```bash
# Clean old logs daily
0 2 * * * php /path/to/replypilot/scripts/cleanup.php

# Process email queue every 5 minutes
*/5 * * * * php /path/to/replypilot/scripts/process_queue.php
```

### Security Checklist

- [ ] Changed default installer token
- [ ] Set strong admin password
- [ ] Configured HTTPS (SSL/TLS)
- [ ] Restricted admin panel access
- [ ] Reviewed file permissions
- [ ] Enabled firewall rules
- [ ] Configured backup strategy

## Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check `.htaccess` is being processed
- Verify PHP version meets requirements
- Review error logs in `storage/logs/`

**Database Connection Failed**
- Verify credentials in `.env`
- Check MySQL service is running
- Ensure database exists

**Blank Page**
- Enable PHP error reporting
- Check PHP memory limit
- Review server error logs

**Email Not Sending**
- Verify SMTP credentials
- Check firewall for SMTP port
- Test with `admin/send_email.php`

### Getting Help

1. Check documentation in `docs/` directory
2. Review `docs/DEBUG.md` for debugging tips
3. Contact support: support@fluentthemes.com

## Updating

To update to the latest version:

1. Backup your database and `.env` file
2. Download the latest release
3. Extract files (preserve `.env` and `storage/`)
4. Run migrations: `php scripts/auto_migrate.php`
5. Clear any caches
6. Test thoroughly

## License

GPL License - see [LICENSE](LICENSE) file for details.
