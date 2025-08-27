# ReplyPilot AI

An intelligent customer support automation system powered by multiple AI providers (OpenAI, Claude, Gemini) that automatically categorizes, analyzes, and responds to customer inquiries with human-like understanding.

## Overview

ReplyPilot AI is a modular PHP-based customer support automation platform designed to streamline email and form submissions processing. It features automatic ticket generation, intelligent categorization, AI-powered response generation, and comprehensive analytics tracking. The system supports multiple AI providers and includes both user-facing submission forms and a full-featured admin dashboard.

The system currently integrates the **Envato Market license API** and is designed to be easily extended to support other license APIs, and future use cases. It automatically replies to user messages in a custom tone (e.g., Friendly, Professional), categorizes submissions (Sales, Support, Spam), and stores everything securely in a MySQL database.

### ✨ Key Features

- 💬 **Multi-Provider AI Integration**: Seamlessly switch between OpenAI GPT, Anthropic Claude, and Google Gemini  
- 🔒 **Purchase Code Validation** Via Envato Market API (optional)  
- 🗂️ **Intelligent Categorization**: Automatically classify submissions into predefined categories  
- 🤖 **Smart Response Generation**: Context-aware, personalized AI responses  
- 🎟️ **Ticket Tracking System**: Unique ticket IDs for every submission with tracking interface  
- 📊 **Analytics Dashboard**: Comprehensive metrics and reporting capabilities  
- ⚡ **Response Caching**: Optimize API costs with intelligent response caching  
- 📩 **Email Notifications**: Automated admin alerts and customer confirmations  
- 🛡️ **Security Focused**: CSRF protection, input validation, and secure session handling  
- 🌐 **Cross-Platform**: Works on Linux, and standard web hosting  
- 🎯 **Tone Selector** For controlling the reply tone  
- 📧 **PHPMailer Integration** To email AI replies to visitors and admin  
- 📂 **Modular Structure** Using organized OOP-based architecture  
- 🧪 **Installer Tool** For quick browser-based setup  

---

## 🛠️ Tech Stack & Requirements

### 💻 System Requirements
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

### 🧩 Technology Stack
- **Backend**: PHP 7.4+ with OOP architecture  
- **Database**: MySQL/MariaDB with PDO  
- **Frontend**: HTML5, CSS3, Vanilla JavaScript  
- **AI Providers**: OpenAI API, Anthropic Claude API, Google Gemini API  
- **Architecture**: MVC-inspired with Repository pattern  
- **Security**: CSRF tokens, prepared statements, input sanitization  

---

## 📦 Installation

### 📥 Method 1: ZIP Archive Installation (Recommended)

1. **Download and Extract**
   ```bash
   # Download the latest release
   wget https://github.com/fluent-themes/replypilot-ai/archive/main.zip
   
   # Extract to your web server directory
   unzip main.zip -d /var/www/html/
   cd /var/www/html/replypilot-ai
   ```

2. **Set Directory Permissions**
   ```bash
   # Linux/Unix
   chmod 755 storage/
   chmod 755 storage/logs/
   chmod 755 storage/mail/
   
   # Ensure web server can write
   chown -R www-data:www-data storage/
   ```

3. **Configure Web Server**
   
   For Apache, ensure `.htaccess` files are enabled:
   ```apache
   <Directory /var/www/html/replypilot-ai>
       AllowOverride All
       Require all granted
   </Directory>
   ```

4. **Run Web Installer**
   
   Navigate to your installation URL with the setup token:
   ```
   https://yourdomain.com/?page=install&token=setup123
   ```

5. **Complete Installation Wizard**
   - Enter database credentials  
   - Configure AI provider API keys  
   - Set Envato API token (optional) 
   - Configure email settings (optional)

6. **🔑 Security: Change Default Token**
   
   **IMPORTANT**: After installation, immediately change the default setup token:
   - Login to admin panel: `https://yourdomain.com/admin/`  
   - Navigate to Settings → Advanced Settings  
   - Update the installer token  
   - Save changes  

---

### 🔧 Method 2: Manual Installation

1. **Clone or Download Repository**
   ```bash
   git clone https://github.com/fluent-themes/replypilot-ai.git
   cd replypilot-ai
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE replypilot_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'replypilot_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON replypilot_db.* TO 'replypilot_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database and API credentials
   nano .env
   ```

4. **Run Database Migrations**
   ```bash
   php scripts/auto_migrate.php
   ```

---

## 📚 Vendor/Dependencies

### 📦 Core Dependencies
The system is designed to work with minimal dependencies. Optional Composer support is available:

```json
{
    "require": {
        "php": ">=7.4",
        "ext-pdo": "*",
        "ext-curl": "*",
        "ext-json": "*"
    }
}
```

### 🎼 Optional: Using Composer
If you prefer using Composer for autoloading:

```bash
composer install --no-dev
```

The system will automatically detect and use Composer autoloader if available, otherwise falls back to built-in autoloading.

---

## 🐞 Tips & Debugging

### ⚠️ Common Issues
- 🛑 **500 Internal Server Error**  
  - Check PHP error logs: `storage/logs/error.log`  
  - Verify `.htaccess` is being processed  
  - Ensure all required PHP extensions are installed  

- 🔗 **Database Connection Failed**  
  - Verify credentials in `.env` file  
  - Check MySQL service is running  
  - Ensure database exists and user has permissions  

- 🤖 **AI Provider Not Responding**  
  - Verify API keys are correct  
  - Check API rate limits  
  - Review provider-specific error messages in logs  

- 📧 **Email Not Sending**  
  - Verify SMTP settings in admin panel  
  - Check firewall rules for SMTP ports  
  - Test with `admin/send_email.php`  

### 🔍 Debug Mode
Enable debug mode for detailed error messages:

1. Edit `.env` file:
   ```
   APP_DEBUG=true
   APP_ENV=development
   ```

2. Check debug logs:
   ```bash
   tail -f storage/logs/debug.log
   ```

### 🚀 Performance Optimization
- ⚡ Enable response caching in admin settings  
- 🗄️ Configure proper MySQL indexes  
- 🌐 Use CDN for static assets  
- 🔥 Enable PHP OPcache  

---

## 📖 Documentation

### 👤 User Documentation
- **Installation Guide**: `INSTALL.md`
- **Admin Manual**: `docs/admin-guide.md`  
- **API Integration**: `docs/api-integration.md`  
- **Troubleshooting**: `docs/DEBUG.md`  

### 👨‍💻 Developer Documentation
- **Architecture Overview**: `docs/architecture.md`  
- **Endpoint Map**: `docs/audit/EndpointMap.md`  
- **Security Audit**: `docs/security-audit.md`  
- **Contributing Guide**: `CONTRIBUTING.md`

### ⚙️ Configuration Files
- `.env.example` - Production environment template
- `.env.mockmode` - Mock/Test environment template

---

## 🔐 Security

### 🛡️ Security Features
- 🛡️ **CSRF Protection**: All forms include CSRF token validation  
- 🗄️ **SQL Injection Prevention**: PDO prepared statements throughout  
- ✨ **XSS Protection**: Input sanitization and output escaping  
- ⏱️ **Session Security**: Secure session handling with timeout  
- 🔑 **Access Control**: Admin authentication with guard middleware  
- 📉 **Rate Limiting**: Built-in rate limiting for API endpoints  

### 📩 Reporting Security Issues
If you discover a security vulnerability, please email support@fluentthemes.com instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

### 📝 Security Best Practices
1. 🔑 Always change default installer token after setup  
2. 🔒 Use strong passwords for admin accounts  
3. ♻️ Keep PHP and dependencies updated  
4. 📜 Regularly review access logs  
5. 🌐 Enable HTTPS in production  
6. 🖥️ Restrict admin panel access by IP if possible  

---

## 📜 License

This project is licensed under the GPL-3.0-or-later License - see the [LICENSE](LICENSE) file for details.

```
GPL-3.0-or-later License

Copyright (c) 2025 Md Mazharul Islam / Fluent-Themes
```

---

## 💡 Support
For support, please:  
1. 📖 Check the documentation in the `docs/` directory  
2. 🔍 Review closed issues on GitHub  
3. 📩 Contact support: support@fluentthemes.com  

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes and version history.

---

## 🙌 Credits

- Built with ❤️ using PHP
- Maintained by [Fluent Themes](https://fluentthemes.com/)

---

**Current Version**: 1.0.0
**Last Updated**: August 27, 2025
**Status**: Production Ready
