# CLAUDE.md - Qbnox SMTP Plugin

## Project Overview

**Plugin Name:** Qbnox Systems – SMTP Plugin  
**Version:** 4.5.2  
**Type:** WordPress Multisite Plugin  
**Tech Stack:** PHP 8.0+, React (WordPress wp-element), REST API  
**Purpose:** Enterprise SMTP plugin with Brevo API integration, multisite support, webhooks, and email analytics

## Repository Information

- **GitHub:** https://github.com/ugenkudupudiqbnox/qbnox-smtp
- **License:** MIT
- **Author:** Qbnox Systems
- **Contact:** ugen@qbnox.com

## Architecture Overview

### Core Components

1. **Settings System** (`includes/class-settings.php`)
   - Manages plugin configuration
   - Stores settings in `wp_sitemeta` (multisite)
   - Default configuration structure with SMTP, Brevo, OAuth, and failover settings

2. **Email Delivery** (`includes/class-mailer.php`)
   - Routes emails based on configuration
   - Supports multiple delivery methods:
     - Brevo API (recommended)
     - Brevo SMTP relay
     - Custom SMTP
     - OAuth (Gmail, Outlook)

3. **Brevo API Integration** (`includes/class-brevo-api.php`)
   - Direct Brevo API v3 implementation
   - Handles transactional email sending
   - Supports HTML/plain text, CC, BCC
   - Returns detailed delivery status

4. **REST API** (`includes/class-rest.php`)
   - Endpoint: `/wp-json/qbnox-smtp/v1/settings`
   - Endpoint: `/wp-json/qbnox-smtp/v1/test-mail`
   - Endpoint: `/wp-json/qbnox-smtp/v1/analytics`
   - Auto-saves Brevo API key to `api.txt`

5. **Admin UI** (`assets/admin-app.js`)
   - React-based interface using `wp.element`
   - Provider selection (Custom, Brevo, Postmark, SES)
   - Brevo method selection (API/SMTP)
   - Test email functionality
   - Real-time settings updates

6. **Webhooks** (`includes/class-webhooks.php`)
   - Brevo webhook handler: `/wp-json/qbnox-smtp/v1/webhook/brevo`
   - Amazon SES webhook handler: `/wp-json/qbnox-smtp/v1/webhook/ses`

7. **Email Logging** (`includes/class-logger.php`)
   - Database-based email logging
   - Tracks delivery, bounces, opens, clicks

## File Structure

```
qbnox-smtp/
├── includes/
│   ├── class-settings.php      # Settings management
│   ├── class-mailer.php        # Email delivery routing
│   ├── class-brevo-api.php     # Brevo API integration
│   ├── class-rest.php          # REST API endpoints
│   ├── class-admin-ui.php      # Admin page registration
│   ├── class-logger.php        # Email logging
│   ├── class-webhooks.php      # Webhook handlers
│   ├── class-oauth.php         # OAuth implementation
│   └── helpers.php             # Utility functions
├── assets/
│   ├── admin-app.js            # React admin interface
│   └── admin.css               # Admin styles
├── qbnox-smtp.php              # Main plugin file
├── README.md                   # Documentation
├── DEVELOPERS.md               # Development guide
├── composer.json               # PHP dependencies
└── api.txt                     # Brevo API key reference (auto-generated)
```

## Development Guidelines

### Code Standards

1. **PHP**
   - Use strict types: `declare(strict_types=1);`
   - Follow WordPress coding standards
   - Type hints required for all function parameters and return types
   - No deprecated WordPress functions

2. **JavaScript**
   - Use WordPress `wp.element` (React)
   - Functional components with hooks
   - Use `wp.apiFetch` for API calls
   - No jQuery dependencies

3. **Database**
   - Use `wp_sitemeta` for network-wide settings
   - Never use raw SQL - use WordPress DB API
   - Use `update_site_option()` and `get_site_option()` for settings

### Settings Structure

```php
[
    'smtp' => [
        'provider'    => 'brevo',      // Provider selection
        'host'        => '',
        'port'        => 587,
        'encryption'  => 'tls',
        'username'    => '',
        'password'    => '',
        'from_email'  => '',
        'from_name'   => 'Qbnox Systems',
    ],
    'brevo' => [
        'enabled'     => true,
        'method'      => 'api',         // 'api' or 'smtp'
        'api_key'     => '',            // Brevo API key
        'smtp_host'   => 'smtp-relay.brevo.com',
        'smtp_port'   => 587,
        'smtp_user'   => '',
        'smtp_pass'   => '',
        'from_email'  => '',
        'from_name'   => '',
    ],
    'failover' => [...],
    'oauth' => [...],
]
```

## Important Notes

### Brevo API Integration

- **API Endpoint:** `https://api.brevo.com/v3/smtp/email`
- **Authentication:** API key in header: `api-key: xkeysib-...`
- **Success Status:** HTTP 201
- **Message ID:** Returned in response for tracking
- **API Key Storage:** Auto-saved to `api.txt` for reference (600 permissions)

### Settings Persistence

**CRITICAL:** Always use `Qbnox_SMTP_Settings::save($data)` to save settings, NOT direct `update_site_option()`. This ensures:
- Proper structure validation
- Default values merge
- No duplicate entries in database

**Common Issue:** Multiple `qbnox_smtp_network` entries in `wp_sitemeta` cause `get_site_option()` to fail. Always check for duplicates:
```sql
SELECT COUNT(*) FROM wp_sitemeta WHERE meta_key = 'qbnox_smtp_network';
```

### Email Delivery Flow

1. WordPress calls `wp_mail()`
2. Plugin intercepts via `phpmailer_init` hook OR `pre_wp_mail` filter
3. If Brevo API enabled → `Qbnox_SMTP_Brevo_API::send_email()`
4. If Brevo SMTP or Custom SMTP → `Qbnox_SMTP_Mailer::configure()`
5. Success/failure logged via `wp_mail_failed` hook

### Admin UI Behavior

- Settings loaded via: `GET /wp-json/qbnox-smtp/v1/settings`
- Settings saved via: `POST /wp-json/qbnox-smtp/v1/settings`
- Test email via: `POST /wp-json/qbnox-smtp/v1/test-mail`
- Requires `manage_network_options` capability
- Caching: WordPress object cache + browser cache

### Provider Selection Logic

```javascript
// When provider changes:
if (provider === 'brevo') {
  cfg.brevo.enabled = true;
  cfg.brevo.method = cfg.brevo.method || 'api'; // Default to API
}

// Conditional rendering:
if (cfg.smtp?.provider === 'brevo' && cfg.brevo?.method === 'api') {
  // Show: API Key, From Email, From Name
  // Hide: SMTP Host, Port, Username, Password
}
```

## Testing

### Manual Testing

1. **Test Email Sending:**
   - Go to: Network Admin → Settings → Qbnox SMTP
   - Enter test email address
   - Click "Send Test Email"
   - Check recipient inbox (and spam folder)

2. **Check Brevo Dashboard:**
   - https://app.brevo.com/transactional/emails
   - Verify message appears with correct status
   - Check for bounces/blocks

3. **Verify API Key:**
   - Check `api.txt` exists: `ls -la /opt/www/pressbooksoss-bedrock/api.txt`
   - Permissions should be 600: `-rw------- 1 www-data www-data`

4. **Check Error Logs:**
   ```bash
   tail -f /opt/log/apache2/pressbook.braou.ac.in-error.log | grep -i "brevo\|smtp"
   ```

### Common Issues

**Issue:** Settings not saving
- **Cause:** Multiple `qbnox_smtp_network` entries in database
- **Fix:** Delete duplicates, keep only one entry

**Issue:** Email not received
- **Check:** Brevo dashboard for delivery status
- **Check:** Spam folder
- **Check:** Email not in unsubscribe list (Brevo Contacts → Blocklist)

**Issue:** ECONNREFUSED
- **Cause:** Web server not running
- **Fix:** `systemctl status apache2` or `systemctl status nginx`

**Issue:** "Permission denied" on plugin files
- **Fix:** `chown -R www-data:www-data /opt/www/pressbooksoss-bedrock/web/app/plugins/qbnox-smtp/`

## Deployment

### Version Update Process

1. **Update version number:**
   ```bash
   # In qbnox-smtp.php:
   # - Plugin header: Version: X.X.X
   # - Constant: QBNOX_SMTP_VERSION
   
   # In README.md:
   # - Current stable version: **X.X.X**
   ```

2. **Commit and tag:**
   ```bash
   git add qbnox-smtp.php README.md
   git commit -m "Bump version to X.X.X"
   git tag -a vX.X.X -m "Release vX.X.X - Description"
   git push origin main
   git push origin vX.X.X
   ```

3. **Create GitHub Release:**
   - Go to: https://github.com/ugenkudupudiqbnox/qbnox-smtp/releases/new
   - Select tag
   - Click "Generate release notes"
   - Publish release

### Production Deployment

```bash
# Navigate to plugin directory
cd /opt/www/pressbooksoss-bedrock/web/app/plugins/qbnox-smtp

# Pull latest changes
git pull origin main

# Fix permissions
chown -R www-data:www-data .
chmod -R 755 .
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;

# Clear WordPress cache
cd /opt/www/pressbooksoss-bedrock
sudo -u www-data wp cache flush
```

## Security

### API Key Storage

- **Database:** Encrypted in `wp_sitemeta` (WordPress handles encryption)
- **File:** `api.txt` with 600 permissions (read/write owner only)
- **Never commit:** Add to `.gitignore`

### Permissions Required

- `manage_network_options` - For settings access
- `www-data` - File ownership for web server

### Input Validation

- All email addresses validated with `is_email()`
- API responses sanitized before display
- SQL injection prevented via WordPress DB API
- XSS prevented via `esc_html()`, `esc_url()`, etc.

## WordPress Multisite Specifics

- Settings stored in `wp_sitemeta` (network-wide)
- Use `get_site_option()` and `update_site_option()`
- Network Admin menu: `network_admin_menu` hook
- Requires `Network: true` in plugin header
- Permission checks: `current_user_can('manage_network_options')`

## Contact & Support

- **Developer:** Ugendreshwar Kudupudi
- **Email:** ugen@qbnox.com
- **Phone:** +91 90085 11933
- **Company:** Qbnox Systems Private Limited
- **Website:** https://www.qbnoxsystems.com
- **GitHub Issues:** https://github.com/ugenkudupudiqbnox/qbnox-smtp/issues

## Additional Resources

- **Brevo API Docs:** https://developers.brevo.com/
- **WordPress Multisite:** https://developer.wordpress.org/plugins/multisite/
- **WordPress REST API:** https://developer.wordpress.org/rest-api/
- **React (wp.element):** https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/

---

**Last Updated:** 2026-11-13  
**CLAUDE.md Version:** 1.0
