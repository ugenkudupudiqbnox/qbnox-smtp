<?php
use League\OAuth2\Client\Provider\Google;
use TheNetworg\OAuth2\Client\Provider\Azure;

add_action('admin_post_qbnox_oauth_start','qbnox_oauth_start');
add_action('admin_post_qbnox_oauth_callback','qbnox_oauth_callback');

function qbnox_oauth_start(){
  if (!current_user_can('manage_network_options')) wp_die('Unauthorized');

  $provider = sanitize_text_field($_GET['provider']);
  $redirect = admin_url('admin-post.php?action=qbnox_oauth_callback&provider='.$provider);

  if ($provider === 'google') {
    $p = new Google([
      'clientId' => get_site_option('qbnox_google_client'),
      'clientSecret' => qbnox_decrypt(get_site_option('qbnox_google_secret')),
      'redirectUri' => $redirect,
    ]);
    wp_redirect($p->getAuthorizationUrl(['scope'=>['https://mail.google.com/']]));
    exit;
  }

  if ($provider === 'microsoft') {
    $p = new Azure([
      'clientId' => get_site_option('qbnox_ms_client'),
      'clientSecret' => qbnox_decrypt(get_site_option('qbnox_ms_secret')),
      'tenant' => get_site_option('qbnox_ms_tenant','common'),
      'redirectUri' => $redirect,
    ]);
    wp_redirect($p->getAuthorizationUrl(['scope'=>['offline_access','SMTP.Send']]));
    exit;
  }
}

function qbnox_oauth_callback(){
  if (!current_user_can('manage_network_options')) wp_die('Unauthorized');

  $provider = sanitize_text_field($_GET['provider']);
  $code = sanitize_text_field($_GET['code']);

  if ($provider === 'google') {
    $p = new Google([
      'clientId' => get_site_option('qbnox_google_client'),
      'clientSecret' => qbnox_decrypt(get_site_option('qbnox_google_secret')),
      'redirectUri' => admin_url('admin-post.php?action=qbnox_oauth_callback&provider=google'),
    ]);
  } else {
    $p = new Azure([
      'clientId' => get_site_option('qbnox_ms_client'),
      'clientSecret' => qbnox_decrypt(get_site_option('qbnox_ms_secret')),
      'tenant' => get_site_option('qbnox_ms_tenant','common'),
      'redirectUri' => admin_url('admin-post.php?action=qbnox_oauth_callback&provider=microsoft'),
    ]);
  }

  $token = $p->getAccessToken('authorization_code', ['code'=>$code]);

  update_site_option('qbnox_oauth_token', [
    'access'  => qbnox_encrypt($token->getToken()),
    'refresh' => qbnox_encrypt($token->getRefreshToken()),
    'expires' => $token->getExpires(),
    'provider'=> $provider,
  ]);

  wp_redirect(network_admin_url('admin.php?page=qbnox-smtp-logs'));
  exit;
}
