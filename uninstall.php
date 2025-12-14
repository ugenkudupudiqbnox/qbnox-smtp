<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
delete_site_option('qbnox_smtp_network');
delete_site_option('qbnox_smtp_oauth');
