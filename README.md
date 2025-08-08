# ReplyPilot AI

**ReplyPilot AI** is a lightweight, modular PHP application that turns your contact form into an intelligent support responder — powered by OpenAI and integrated with optional license validation.

The system currently integrates the **Envato Market license API** and is designed to be easily extended to support other license APIs, AI models, and future use cases. It automatically replies to user messages in a custom tone (e.g., Friendly, Professional), categorizes submissions (Sales, Support, Spam), and stores everything securely in a MySQL database.

---

## ✨ Features

- 🔒 **Purchase Code Validation** via Envato Market API (optional)
- 💬 **GPT-4o Integration** for AI-generated replies and categorization
- 🎯 **Tone Selector** for controlling the reply tone
- 📧 **PHPMailer Integration** to email AI replies to visitors and admin
- 🗃️ **MySQL Logging** of all form submissions
- 🧑‍💼 **Admin Panel** to view and manage entries (no login yet)
- 📂 **Modular Structure** using organized OOP-based architecture
- 🧪 **Installer Tool** for quick browser-based setup
- ✅ **Composer-ready** and ZIP-deployable (includes vendor folder)

---

## 🗂 Directory Structure (Simplified)

```
ai-contact-form-auto-responder/
├── public/               # Web root
│   ├── index.php         # Contact form endpoint
│   ├── thank-you.php     # Success page
│   ├── installer.php     # Browser-based installer
│   ├── assets/           # CSS, JS, and favicon
├── admin/                # Admin dashboard
│   ├── views/            # Templates
├── app/                  # Application logic
│   ├── Core/, Support/, Services/, Http/, etc.
│   ├── Installer/        # Setup logic for .env + DB
│   └── Models/, Repositories/
├── config/               # Config files
├── database/             # Migration scripts
├── logs/                 # Auto-generated logs
├── tests/                # PHPUnit-ready
├── .env.example
├── bootstrap.php
├── composer.json
├── README.md
```

---

## 🚀 Installation

### Method A – ZIP Upload (Recommended for shared hosting)

1. Download and extract the ZIP into your hosting directory (e.g., `/public_html/replypilot/`)
2. Create a MySQL database and user via cPanel.
3. Rename `.env.example` → `.env`, then edit it to add:
   - `OPENAI_API_KEY` from https://platform.openai.com
   - Database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`)
   - `INSTALL_TOKEN` – any setup keyword (e.g. `setup123`)
   - Leave license fields blank if you don’t need validation
4. Visit the installer in your browser:
   ```
   https://yourdomain.com/replypilot/?page=install&token=setup123
   ```
5. Fill out the short form and complete installation.

### Method B – Composer / Git Workflow (Dev use)

```bash
git clone https://github.com/yourusername/replypilot-ai.git
cd replypilot-ai
composer install --no-dev --optimize-autoloader
cp .env.example .env
# Edit your .env with appropriate values
php -S localhost:8000 -t public
```

---

## 🧪 Updating

### For ZIP-based installs:
- Download the latest ZIP
- Overwrite your existing files (leave `.env` and DB intact)

### For Composer-based installs:
```bash
git pull
composer install --no-dev
```

---

## 🧰 Troubleshooting

| Problem                             | Fix                                                                 |
|-------------------------------------|----------------------------------------------------------------------|
| Blank screen / 500 error            | Check `logs/app.log` and your hosting error logs                    |
| “Invalid install token”             | Make sure the token in the URL matches `INSTALL_TOKEN` in `.env`    |
| OpenAI request timeout              | Increase `TIMEOUT` in `.env` or verify outbound connectivity        |
| Composer PHP version mismatch       | Update `"php": "^8.x"` in composer.json and re-run `composer update`|

---

## 📄 License

This project is licensed under the MIT License.  
See [`LICENSE`](LICENSE) for full terms.

---

## 🙌 Credits

- Built with ❤️ using PHP, OpenAI GPT-4o, and PHPMailer
- Maintained by [Fluent Themes](https://fluentthemes.com/)

---

## 🔧 Future Roadmap

- Admin authentication system
- Filterable admin submission view
- Integration with other AI APIs (Claude, Gemini, etc.)
- License API adapter interface (non-Envato providers)
