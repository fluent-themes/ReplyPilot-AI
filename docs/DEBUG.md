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

## Debug artifacts

- **Local:** `composer run test:pack` → ZIP created in `artifacts/`.
- **GitHub Actions:** download artifact `debug-<run_number>` from the workflow run.
- In mock mode with `INSTALLER_MOCK=true`, installer DB actions are skipped; logs are written instead. The debug ZIP can be downloaded from the GitHub Actions run's **Artifacts** section.
