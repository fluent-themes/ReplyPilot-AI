# Debugging Setup

1. `cp .env.example .env`
2. `composer install`
3. `composer test`

Logs are written to `storage/logs/app.log`.
Mock emails are saved to `storage/mail/` as `.eml` files.

## Production

Edit `.env` to provide real credentials:
- Set a real `OPENAI_API_KEY` to enable OpenAI.
- Use `MAIL_TRANSPORT=smtp` and fill SMTP_* values to send real mail.
- Define `DB_CONNECTION` and DB_* values to use a real database.
Mocks disable automatically when these are set.
