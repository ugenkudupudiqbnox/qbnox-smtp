<?php
add_action('qbnox_health_check', function(){
  $token = get_site_option('qbnox_oauth_token');
  if(!$token) return;

  if($token['expires'] < time()+3600){
    wp_mail(
      get_site_option('admin_email'),
      'Qbnox SMTP: OAuth token expiring',
      'OAuth token will expire within 1 hour. Re-authorization recommended.'
    );
  }
});
