<?php
/**
 * Plugin Name: Qbnox SMTP
 * Description: Enterprise-grade SMTP & OAuth mailer for WordPress Multisite.
 * Version: 4.4.0
 * Author: Qbnox Systems Pvt Ltd
 * License: MIT
 * Network: true
 */
if (!defined('ABSPATH')) exit;
define('QBNOX_SMTP_PATH', plugin_dir_path(__FILE__));
define('QBNOX_SMTP_VERSION', '4.4.0');
require QBNOX_SMTP_PATH.'includes/bootstrap.php';
