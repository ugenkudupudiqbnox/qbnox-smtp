<?php
require_once QBNOX_SMTP_PATH.'includes/install.php';
require_once QBNOX_SMTP_PATH.'includes/crypto.php';
require_once QBNOX_SMTP_PATH.'includes/config.php';
require_once QBNOX_SMTP_PATH.'includes/settings-network.php';
require_once QBNOX_SMTP_PATH.'includes/settings-site.php';
require_once QBNOX_SMTP_PATH.'includes/oauth.php';
require_once QBNOX_SMTP_PATH.'includes/phpmailer.php';
require_once QBNOX_SMTP_PATH.'includes/logger.php';
require_once QBNOX_SMTP_PATH.'includes/log-ui.php';
require_once QBNOX_SMTP_PATH.'includes/health.php';
require_once QBNOX_SMTP_PATH.'includes/retention.php';
require_once QBNOX_SMTP_PATH.'includes/cli.php';

register_activation_hook(QBNOX_SMTP_PATH.'qbnox-smtp.php','qbnox_install');
register_activation_hook(QBNOX_SMTP_PATH.'qbnox-smtp.php','qbnox_schedule_cron');
register_deactivation_hook(QBNOX_SMTP_PATH.'qbnox-smtp.php','qbnox_clear_cron');
