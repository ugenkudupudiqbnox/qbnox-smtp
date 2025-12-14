<?php
/**
 * Plugin Name: Qbnox Systems – Advanced SMTP
 * Description: Enterprise SMTP plugin with OAuth, Webhooks, Analytics (PHP 8+)
 * Version: 2.0.0
 * Author: Qbnox Systems
 * Network: true
 */
defined('ABSPATH') || exit;

define('QBNOX_SMTP_PATH', plugin_dir_path(__FILE__));
define('QBNOX_SMTP_URL', plugin_dir_url(__FILE__));

require_once QBNOX_SMTP_PATH.'includes/helpers.php';
require_once QBNOX_SMTP_PATH.'includes/class-logger.php';
require_once QBNOX_SMTP_PATH.'includes/class-settings.php';
require_once QBNOX_SMTP_PATH.'includes/class-oauth.php';
require_once QBNOX_SMTP_PATH.'includes/class-mailer.php';
require_once QBNOX_SMTP_PATH.'includes/class-rest.php';
require_once QBNOX_SMTP_PATH.'includes/class-webhooks.php';
require_once QBNOX_SMTP_PATH . 'includes/class-admin-ui.php';

register_activation_hook(__FILE__, function () {
    Qbnox_SMTP_Logger::install();

    if (get_site_option('qbnox_smtp_network') === false) {
        update_site_option(
            'qbnox_smtp_network',
            Qbnox_SMTP_Settings::defaults()
        );
    }
});

/**
 * Capture last mail error safely for test email
 */
add_action('wp_mail_failed', function (WP_Error $error) {
    update_site_option(
        'qbnox_smtp_last_error',
        [
            'message' => $error->get_error_message(),
            'data'    => $error->get_error_data(),
            'time'    => current_time('mysql'),
        ]
    );
});


Qbnox_SMTP_Mailer::init();
Qbnox_SMTP_REST::init();
Qbnox_SMTP_Admin_UI::init();
