<?php
add_action('network_admin_menu', function () {
  add_menu_page('Qbnox SMTP Logs','Qbnox SMTP Logs','manage_network_options','qbnox-smtp-logs','qbnox_render_logs');
});

function qbnox_render_logs(){
  global $wpdb;
  $rows = $wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}qbnox_mail_log ORDER BY created DESC LIMIT 200");

  echo '<div class="wrap"><h1>Email Logs</h1><table class="widefat fixed striped">';
  echo '<tr><th>Site</th><th>Status</th><th>Subject</th><th>Error</th><th>Time</th></tr>';

  foreach ($rows as $r) {
    echo '<tr>';
    echo '<td>'.intval($r->site_id).'</td>';
    echo '<td>'.esc_html($r->status).'</td>';
    echo '<td>'.esc_html($r->subject).'</td>';
    echo '<td>'.esc_html($r->error).'</td>';
    echo '<td>'.esc_html($r->created).'</td>';
    echo '</tr>';
  }

  echo '</table></div>';
}
