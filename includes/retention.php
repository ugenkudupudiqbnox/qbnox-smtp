<?php
function qbnox_schedule_cron(){
  if(!wp_next_scheduled('qbnox_log_cleanup')){
    wp_schedule_event(time(), 'daily', 'qbnox_log_cleanup');
  }
  if(!wp_next_scheduled('qbnox_health_check')){
    wp_schedule_event(time(), 'hourly', 'qbnox_health_check');
  }
}

function qbnox_clear_cron(){
  wp_clear_scheduled_hook('qbnox_log_cleanup');
  wp_clear_scheduled_hook('qbnox_health_check');
}

add_action('qbnox_log_cleanup', function(){
  global $wpdb;
  $days = intval(get_site_option('qbnox_log_retention_days', 90));
  $wpdb->query(
    $wpdb->prepare(
      "DELETE FROM {$wpdb->base_prefix}qbnox_mail_log WHERE created < DATE_SUB(NOW(), INTERVAL %d DAY)",
      $days
    )
  );
});
