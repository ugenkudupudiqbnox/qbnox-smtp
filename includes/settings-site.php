<?php
add_action('admin_menu', function(){
  add_options_page('Qbnox SMTP Override','Qbnox SMTP','manage_options','qbnox-smtp-site','qbnox_site_settings');
});

function qbnox_site_settings(){
  if(isset($_POST['save']) && check_admin_referer('qbnox_site')){
    update_option('qbnox_site_override', true);
    update_option('qbnox_smtp_site', [
      'host'=>sanitize_text_field($_POST['host']),
      'port'=>intval($_POST['port']),
      'auth'=>sanitize_text_field($_POST['auth']),
      'username'=>sanitize_text_field($_POST['username']),
      'password'=>qbnox_encrypt($_POST['password']),
      'from_email'=>sanitize_email($_POST['from_email'])
    ]);
    echo '<div class="updated"><p>Site override saved</p></div>';
  }
  ?>
  <div class="wrap">
  <h1>SMTP Site Override</h1>
  <form method="post"><?php wp_nonce_field('qbnox_site'); ?>
  <input name="host" placeholder="SMTP Host">
  <input name="port" value="587">
  <select name="auth"><option value="password">Password</option><option value="oauth">OAuth</option></select>
  <input name="username" placeholder="Username">
  <input name="password" placeholder="Password">
  <input name="from_email" placeholder="From Email">
  <button class="button-primary" name="save">Save</button>
  </form>
  </div><?php
}
