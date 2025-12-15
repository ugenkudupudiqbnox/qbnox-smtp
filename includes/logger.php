<?php
add_action('wp_mail_failed', function ($e) {
  global $wpdb;
  $wpdb->insert($wpdb->base_prefix.'qbnox_mail_log', [
    'site_id' => get_current_blog_id(),
    'recipient' => '',
    'subject' => '',
    'status' => 'failed',
    'error' => $e->get_error_message(),
    'created' => current_time('mysql'),
  ]);
});
