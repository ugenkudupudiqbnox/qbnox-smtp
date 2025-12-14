<?php
/**
 * Plugin Name: Qbnox Systems – Advanced SMTP
 * Description: Enterprise SMTP plugin with Multisite support, OAuth, Webhooks, Analytics and Test Email diagnostics.
 * Version: 2.0.1
 * Author: Qbnox Systems
 * Network: true
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Constants
 */
define('QBNOX_SMTP_PATH', plugin_dir_path(__FILE__));
define('QBNOX_SMTP_URL', plugin_dir_url(__FILE__));

/**
 * Includes
 */
require_once QBNOX_SMTP_PATH . 'includes/helpers.php';
require_once QBNOX_SMTP_PATH . 'includes/class-settings.php';
require_once QBNOX_SMTP_PATH . 'includes/class-logger.php';
require_once QBNOX_SMTP_PATH . 'includes/class-mailer.php';
require_once QBNOX_SMTP_PATH . 'includes/class-rest.php';
require_once QBNOX_SMTP_PATH . 'includes/class-webhooks.php';
require_once QBNOX_SMTP_PATH . 'includes/class-admin-ui.php';

/**
 * Plugin activation
 */
register_activation_hook(__FILE__, function (): void {

    // Create logs table
    Qbnox_SMTP_Logger::install();

    // Initialize default network settings safely
    if (get_site_option('qbnox_smtp_network') === false) {
        update_site_option(
            'qbnox_smtp_network',
            Qbnox_SMTP_Settings::defaults()
        );
    }
});

/**
 * Capture mail failures safely (NO PHPMailer internals)
 */
add_action('wp_mail_failed', function (WP_Error $error): void {

    update_site_option(
        'qbnox_smtp_last_error',
        [
            'message' => $error->get_error_message(),
            'data'    => $error->get_error_data(),
            'time'    => current_time('mysql'),
        ]
    );
});

/**
 * Bootstrap plugin components
 */
add_action('plugins_loaded', function (): void {

    Qbnox_SMTP_Mailer::init();     // SMTP configuration
    Qbnox_SMTP_REST::init();       // REST API (settings, test mail, analytics)
    Qbnox_SMTP_Admin_UI::init();   // Network admin UI

});

