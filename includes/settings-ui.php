<?php
add_action('network_admin_menu', function () {
  add_menu_page('Qbnox SMTP','Qbnox SMTP','manage_network_options','qbnox-smtp-settings','qbnox_smtp_settings_page');
});

function qbnox_smtp_settings_page(){
  if (isset($_POST['save_qbnox']) && check_admin_referer('qbnox_save')) {
    update_site_option('qbnox_smtp_network', [
      'host' => sanitize_text_field($_POST['host']),
      'port' => intval($_POST['port']),
      'auth' => sanitize_text_field($_POST['auth']),
      'username' => sanitize_text_field($_POST['username']),
      'password' => qbnox_encrypt($_POST['password']),
      'from_email' => sanitize_email($_POST['from_email']),
    ]);

    update_site_option('qbnox_google_client', sanitize_text_field($_POST['google_client']));
    update_site_option('qbnox_google_secret', qbnox_encrypt($_POST['google_secret']));

    update_site_option('qbnox_ms_client', sanitize_text_field($_POST['ms_client']));
    update_site_option('qbnox_ms_secret', qbnox_encrypt($_POST['ms_secret']));
    update_site_option('qbnox_ms_tenant', sanitize_text_field($_POST['ms_tenant']));

    echo '<div class="updated notice"><p>Settings saved.</p></div>';
  }

  $cfg = get_site_option('qbnox_smtp_network', []);
  ?>
  <div class="wrap">
  <h1>Qbnox SMTP Settings</h1>
  <form method="post">
  <?php wp_nonce_field('qbnox_save'); ?>
  <h2>SMTP</h2>
  <input name="host" placeholder="SMTP Host" value="<?php echo esc_attr($cfg['host'] ?? ''); ?>"><br>
  <input name="port" placeholder="Port" value="<?php echo esc_attr($cfg['port'] ?? 587); ?>"><br>
  <select name="auth">
    <option value="password">SMTP Password</option>
    <option value="oauth">OAuth (Google / Microsoft)</option>
    <option value="none">Relay (No Auth)</option>
  </select><br>
  <input name="username" placeholder="SMTP Username" value="<?php echo esc_attr($cfg['username'] ?? ''); ?>"><br>
  <input name="password" placeholder="SMTP Password"><br>
  <input name="from_email" placeholder="From Email" value="<?php echo esc_attr($cfg['from_email'] ?? ''); ?>"><br>

  <h2>Google OAuth</h2>
  <input name="google_client" placeholder="Client ID"><br>
  <input name="google_secret" placeholder="Client Secret"><br>
  <a href="<?php echo admin_url('admin-post.php?action=qbnox_oauth_start&provider=google'); ?>">Authorize Google</a>

  <h2>Microsoft OAuth</h2>
  <input name="ms_client" placeholder="Client ID"><br>
  <input name="ms_secret" placeholder="Client Secret"><br>
  <input name="ms_tenant" placeholder="Tenant (optional)"><br>
  <a href="<?php echo admin_url('admin-post.php?action=qbnox_oauth_start&provider=microsoft'); ?>">Authorize Microsoft</a>

  <p><button class="button-primary" name="save_qbnox">Save Settings</button></p>
  </form>
  </div>
  <?php
}
