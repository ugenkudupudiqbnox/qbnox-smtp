<?php
/**
 * Plugin Name: Qbnox Systems – Advanced SMTP
 * Description: Multisite SMTP plugin with OAuth, Webhooks, Analytics (PHP 8+)
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
require_once QBNOX_SMTP_PATH.'includes/class-mailer.php';
require_once QBNOX_SMTP_PATH.'includes/class-rest.php';
require_once QBNOX_SMTP_PATH.'includes/class-webhooks.php';

register_activation_hook(__FILE__, ['Qbnox_SMTP_Logger','install']);
Qbnox_SMTP_Mailer::init();
Qbnox_SMTP_REST::init();
