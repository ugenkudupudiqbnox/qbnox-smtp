<?php
add_action('network_admin_menu', function(){
  add_menu_page('Qbnox SMTP','Qbnox SMTP','manage_network_options','qbnox-smtp-settings','qbnox_net_settings');
});

function qbnox_net_settings(){
  if(isset($_POST['save']) && check_admin_referer('qbnox_net')){
    update_site_option('qbnox_log_retention_days', intval($_POST['retention']));
    echo '<div class=updated><p>Settings saved</p></div>';
  }
  $ret = get_site_option('qbnox_log_retention_days',90);
  ?>
  <div class="wrap">
  <h1>Qbnox SMTP – Network Settings</h1>
  <form method="post"><?php wp_nonce_field('qbnox_net'); ?>
  <h2>Log Retention</h2>
  <input name="retention" value="<?php echo esc_attr($ret); ?>"> days
  <p><button class="button-primary" name="save">Save</button></p>
  </form>
  </div><?php
}
