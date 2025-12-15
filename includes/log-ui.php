<?php
add_action('network_admin_menu',function(){
  add_menu_page('Qbnox SMTP Logs','Qbnox SMTP Logs','manage_network_options','qbnox-smtp-logs','qbnox_logs');
});

function qbnox_logs(){
  global $wpdb;
  if(isset($_GET['export'])){
    header('Content-Type:text/csv');
    header('Content-Disposition:attachment;filename=mail-logs.csv');
    $out=fopen('php://output','w');
    fputcsv($out,['Site','Status','Subject','Error','Time']);
    foreach($wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}qbnox_mail_log") as $r){
      fputcsv($out,[$r->site_id,$r->status,$r->subject,$r->error,$r->created]);
    }
    exit;
  }

  $rows=$wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}qbnox_mail_log ORDER BY created DESC LIMIT 200");
  echo '<div class=wrap><h1>Email Logs</h1><a href="?page=qbnox-smtp-logs&export=1" class=button>Export CSV</a>';
  echo '<table class=widefat><tr><th>Site</th><th>Status</th><th>Subject</th><th>Error</th><th>Time</th></tr>';
  foreach($rows as $r){
    echo "<tr><td>$r->site_id</td><td>$r->status</td><td>$r->subject</td><td>$r->error</td><td>$r->created</td></tr>";
  }
  echo '</table></div>';
}
