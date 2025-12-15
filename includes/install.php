<?php
function qbnox_install() {
  global $wpdb;
  $table = $wpdb->base_prefix . 'qbnox_mail_log';
  $charset = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT,
    recipient TEXT,
    subject TEXT,
    status VARCHAR(20),
    error TEXT,
    created DATETIME
  ) $charset;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
