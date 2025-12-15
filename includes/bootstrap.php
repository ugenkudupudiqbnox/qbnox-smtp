<?php
if (!defined('ABSPATH')) exit;

require_once QBNOX_SMTP_PATH . 'includes/install.php';
require_once QBNOX_SMTP_PATH . 'includes/crypto.php';
require_once QBNOX_SMTP_PATH . 'includes/config.php';
require_once QBNOX_SMTP_PATH . 'includes/oauth.php';
require_once QBNOX_SMTP_PATH . 'includes/phpmailer.php';
require_once QBNOX_SMTP_PATH . 'includes/logger.php';
require_once QBNOX_SMTP_PATH . 'includes/log-ui.php';
require_once QBNOX_SMTP_PATH . 'includes/diagnostics.php';
require_once QBNOX_SMTP_PATH . 'includes/cli.php';

register_activation_hook(QBNOX_SMTP_PATH . 'qbnox-smtp.php', 'qbnox_install');
