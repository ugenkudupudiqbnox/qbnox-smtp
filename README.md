# Qbnox Systems – SMTP Plugin

SMTP plugin for WordPress with advanced features including Amazon SES and Brevo webhook support, multisite compatibility, OAuth authentication, and comprehensive email analytics.

---

## Features

- **🚀 Reliable SMTP**: Configure reliable SMTP email delivery for your WordPress site
- **🌐 Multisite Support**: Network-wide configuration for WordPress multisite installations
- **🔐 OAuth Authentication**: Secure authentication support for modern email providers
- **📊 Email Analytics**: Track email delivery, opens, bounces, and engagement metrics
- **🔔 Webhook Integration**: Real-time webhooks for Amazon SES and Brevo (SendinBlue)
- **🧪 Test Email Diagnostics**: Built-in tools to test and troubleshoot email delivery
- **📝 Email Logging**: Comprehensive logging of all outgoing emails for debugging

---

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher

---

## Installation

### Option 1: Bedrock (Recommended)

For modern WordPress development using [Bedrock](https://roots.io/bedrock/) by Roots:

1. Add the plugin to your Bedrock project using Composer:

```bash
composer require ugenkudupudiqbnox/qbnox-smtp
```

2. The plugin will be automatically installed in the `web/app/plugins/` directory
3. Activate the plugin through the WordPress admin panel or via WP-CLI:

```bash
wp plugin activate qbnox-smtp
```

This method is recommended for professional WordPress development as it provides better dependency management and easier updates.

### Option 2: Download Latest Release

1. Download the latest [`qbnox-smtp.zip`](https://github.com/ugenkudupudiqbnox/qbnox-smtp/releases/tag/v4.4.0) file
2. In your WordPress admin panel, navigate to **Plugins > Add New**
3. Click **Upload Plugin** at the top of the page
4. Choose the downloaded `qbnox-smtp.zip` file
5. Click **Install Now**
6. After installation, click **Activate Plugin**

NOTE: [Releases page](https://github.com/ugenkudupudiqbnox/qbnox-smtp/releases)

### Option 3: Manual Installation

1. Download the latest release [`qbnox-smtp.zip`](https://github.com/ugenkudupudiqbnox/qbnox-smtp/releases/tag/v4.4.0) or from the [Releases page](https://github.com/ugenkudupudiqbnox/qbnox-smtp/releases)
2. Extract the zip file
3. Upload the `qbnox-smtp` folder to `/wp-content/plugins/` directory via FTP or file manager
4. Activate the plugin through the **Plugins** menu in WordPress

### Option 4: Install from Source (Advanced)

If you want to install directly from the GitHub repository:

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/ugenkudupudiqbnox/qbnox-smtp.git
```

Then activate the plugin through the WordPress admin panel.

---

## Configuration

After activation:

1. Navigate to **Settings > Qbnox SMTP** in your WordPress admin panel
2. Configure your SMTP server settings:
   - SMTP Host
   - SMTP Port
   - Encryption (TLS/SSL)
   - Authentication credentials
3. Configure webhook settings for Amazon SES or Brevo (optional)
4. Use the **Test Email** feature to verify your configuration

For **WordPress Multisite**: Network admins can configure settings network-wide under **Network Admin > Settings > Qbnox SMTP**.

---

## Documentation

- [DEVELOPERS.md](DEVELOPERS.md) - Development setup, coding standards, and contribution guidelines
- [Releases](https://github.com/ugenkudupudiqbnox/qbnox-smtp/releases) - Download the latest version

---

## Support

For issues, questions, or feature requests, please [open an issue](https://github.com/ugenkudupudiqbnox/qbnox-smtp/issues) on GitHub.

---

## License

This plugin is licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

## Version

Current stable version: **4.5.0**
