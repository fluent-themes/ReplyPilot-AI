# ReplyPilot AI - Administrator Guide

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Managing Submissions](#managing-submissions)
4. [Settings Configuration](#settings-configuration)
5. [AI Provider Management](#ai-provider-management)
6. [Email Configuration](#email-configuration)
7. [Category Management](#category-management)
8. [Analytics & Reporting](#analytics--reporting)
9. [System Maintenance](#system-maintenance)
10. [Troubleshooting](#troubleshooting)

## Getting Started

### Accessing the Admin Panel

After installation, access the admin panel at:
```
https://yourdomain.com/admin/
```

Use the admin credentials created during installation or the unlock token specified in your `.env` file.

### First-Time Setup Checklist

1. **Change Default Installer Token** - Critical security step
2. Configure AI Provider (OpenAI/Claude/Gemini)
3. Set up SMTP email settings
4. Create submission categories
5. Enable/disable purchase code validation
6. Configure rate limiting settings
7. Test email delivery
8. Review security settings

## Dashboard Overview

The main dashboard (`/admin/`) provides:

- **Submission Queue**: View and manage incoming customer submissions
- **Quick Stats**: Total submissions, pending replies, response rate
- **Recent Activity**: Latest submissions with status indicators
- **System Health**: Server status and configuration warnings

### Dashboard Actions

- **View Details**: Click on any submission to view full details
- **Generate AI Reply**: Use AI to draft responses
- **Manual Reply**: Compose custom responses
- **Export Data**: Download submissions as CSV
- **Category Assignment**: Organize submissions by type

## Managing Submissions

### Submission Workflow

1. **New Submission Arrives**
   - Appears in dashboard with "Pending" status
   - Admin notification sent (if enabled)
   - Unique ticket ID generated

2. **Review & Categorize**
   - Click submission to view details
   - Assign appropriate category
   - Review customer information

3. **Generate Response**
   - Click "Generate AI Reply" for automated response
   - Edit AI-generated content as needed
   - Or compose manual response

4. **Send Reply**
   - Preview email before sending
   - Click "Send Email" to deliver
   - Status updates to "Replied"

### Submission Details

Each submission includes:

- **Customer Information**: Name, email, submitted date
- **Message Content**: Original customer message
- **AI Analysis**: Category suggestion, sentiment analysis
- **Response History**: All replies sent
- **Ticket Reference**: Unique tracking ID
- **Product Details**: Purchase code if provided

### Bulk Operations

- **Export Selected**: Download multiple submissions
- **Batch Categorize**: Apply category to multiple items
- **Mark as Resolved**: Update status in bulk

## Settings Configuration

### Basic Settings (`/admin/settings.php`)

#### Purchase Code Validation

- **Enable Validation**: Require valid purchase codes
- **Code Required**: Make purchase code mandatory
- **Envato Integration**: Validate against Envato API

#### Submission Settings

- **Auto-Reply**: Enable automatic AI responses
- **Admin Notifications**: Email alerts for new submissions
- **Thank You Page**: Customize confirmation message

### Advanced Settings (`/admin/advanced_settings.php`)

#### AI Provider Configuration

**OpenAI Settings**:
- API Key: Your OpenAI API key
- Model: GPT-3.5-turbo or GPT-4
- Temperature: 0.1-1.0 (creativity level)
- Max Tokens: Response length limit

**Claude Settings**:
- API Key: Your Anthropic API key
- Model: claude-3-opus or claude-3-sonnet
- Max Tokens: 1000-4000

**Gemini Settings**:
- API Key: Your Google AI API key
- Model: gemini-pro
- Safety Settings: Content filtering level

#### Rate Limiting

- **Requests per Minute**: 6-60 (default: 6)
- **Block Duration**: 60-3600 seconds
- **IP-based Limiting**: Enable/disable
- **Session-based Limiting**: Enable/disable

#### Security Settings

- **CSRF Protection**: Always enabled
- **Session Timeout**: 15-120 minutes
- **Admin IP Whitelist**: Restrict access by IP
- **Installer Token**: Change from default

## AI Provider Management

### Testing Providers

Use the test button in Advanced Settings to verify:

1. API key validity
2. Network connectivity
3. Model availability
4. Response generation

### Provider Selection Strategy

**OpenAI GPT**:
- Best for: General customer support
- Strengths: Wide knowledge, consistent tone
- Cost: $0.002-0.03 per request

**Anthropic Claude**:
- Best for: Complex technical queries
- Strengths: Detailed analysis, safety
- Cost: $0.01-0.03 per request

**Google Gemini**:
- Best for: Multi-language support
- Strengths: Fast responses, cost-effective
- Cost: Free tier available

### Fallback Configuration

Set up provider fallback chain:
1. Primary: Your main AI provider
2. Secondary: Backup provider
3. Manual: Alert admin if all fail

## Email Configuration

### SMTP Settings

Configure in Advanced Settings:

- **SMTP Host**: mail.yourdomain.com
- **SMTP Port**: 587 (TLS) or 465 (SSL)
- **SMTP Username**: Your email username
- **SMTP Password**: Your email password
- **Encryption**: TLS recommended
- **From Address**: noreply@yourdomain.com
- **From Name**: Your Company Name

### Email Templates

Customize email templates for:

- **Auto-Reply**: AI-generated responses
- **Manual Reply**: Admin-composed messages
- **Admin Notification**: New submission alerts
- **Thank You**: Confirmation emails

### Testing Email Delivery

1. Navigate to `/admin/send_email.php`
2. Enter test recipient email
3. Send test message
4. Check spam folder if not received
5. Review SMTP logs for errors

## Category Management

### Creating Categories

1. Go to `/admin/categories.php`
2. Click "Add Category"
3. Enter category details:
   - Name: Display name
   - Slug: URL-friendly identifier
   - Description: Internal notes
   - Priority: Sort order
   - Auto-assign keywords: Trigger words

### Category Rules

Set up automatic categorization based on:

- **Keywords**: Specific words/phrases
- **Email Domain**: Customer email domain
- **Product Name**: Associated product
- **Message Length**: Short/medium/long
- **Sentiment**: Positive/negative/neutral

### Category Actions

Assign specific actions per category:

- **Auto-Reply Template**: Category-specific responses
- **Priority Level**: High/medium/low
- **Assignee**: Route to specific admin
- **SLA Timer**: Response time requirement

## Analytics & Reporting

### Dashboard Metrics

Monitor key performance indicators:

- **Response Time**: Average time to first reply
- **Resolution Rate**: Tickets resolved vs open
- **Category Distribution**: Submission types
- **AI Usage**: Automated vs manual replies
- **Customer Satisfaction**: Based on follow-ups

### Reports

Generate reports for:

- Daily/weekly/monthly summaries
- Category performance
- AI provider usage and costs
- Admin activity logs
- Customer trends

### Exporting Data

Export options:

1. **CSV Export**: Spreadsheet-compatible format
2. **JSON Export**: For API integration
3. **PDF Reports**: Formatted summaries
4. **Backup Export**: Complete database dump

## System Maintenance

### Regular Tasks

**Daily**:
- Review pending submissions
- Check system health status
- Monitor error logs

**Weekly**:
- Clear old session files
- Review AI provider usage
- Update category rules
- Export backup

**Monthly**:
- Review analytics trends
- Optimize database
- Update AI provider settings
- Security audit

### Database Maintenance

1. **Optimize Tables**: Run monthly via system health
2. **Clear Old Data**: Remove submissions older than X days
3. **Backup Database**: Before any major changes
4. **Index Optimization**: Check slow query log

### Cache Management

- **Response Cache**: Store AI responses for similar queries
- **Session Cache**: Manage active user sessions
- **Template Cache**: Speed up page rendering
- **Clear Cache**: When updating settings

## Troubleshooting

### Common Issues

**Submissions Not Appearing**:
- Check database connection
- Verify form CSRF tokens
- Review PHP error logs
- Check rate limiting settings

**AI Provider Errors**:
- Verify API key validity
- Check rate limits
- Review provider status page
- Test with provider test tool

**Email Not Sending**:
- Verify SMTP credentials
- Check firewall/port blocking
- Review email logs
- Test with send_email.php

**Login Issues**:
- Clear browser cookies
- Check session timeout settings
- Verify admin token in .env
- Reset via database if needed

### Debug Mode

Enable debugging for detailed logs:

1. Edit `.env` file: `APP_DEBUG=true`
2. Check logs in `storage/logs/`
3. Review browser console for JS errors
4. Use system health page for diagnostics

### Getting Help

If issues persist:

1. Check documentation in `/docs/` directory
2. Review `DEBUG.md` for technical details
3. Contact support: support@fluentthemes.com
4. Include error logs and system info

## Security Best Practices

1. **Regular Updates**: Keep PHP and dependencies current
2. **Strong Passwords**: Use complex admin passwords
3. **IP Restrictions**: Limit admin access by IP
4. **SSL/HTTPS**: Always use encrypted connections
5. **Backup Regularly**: Maintain offsite backups
6. **Monitor Logs**: Check for suspicious activity
7. **Token Rotation**: Change installer token periodically
8. **Database Security**: Use prepared statements only

## Quick Reference

### Important URLs

- Admin Dashboard: `/admin/`
- Settings: `/admin/settings.php`
- Advanced Settings: `/admin/advanced_settings.php`
- Categories: `/admin/categories.php`
- System Health: `/admin/system_health.php`
- Email Test: `/admin/send_email.php`

### Default Limits

- Rate Limit: 6 requests per minute
- Session Timeout: 30 minutes
- Max Upload Size: 2MB
- AI Response Length: 1000 tokens
- CSV Export Limit: 10,000 records

### File Locations

- Configuration: `.env`
- Error Logs: `storage/logs/error.log`
- Debug Logs: `storage/logs/debug.log`
- Email Queue: `storage/mail/`
- Session Files: `storage/sessions/`
- Cache Files: `storage/cache/`

---

**Last Updated**: August 2025
**Version**: 1.0.0
**Support**: support@fluentthemes.com