<?php
use League\OAuth2\Client\Provider\Google;
use TheNetworg\OAuth2\Client\Provider\Azure;

function qbnox_refresh_token_if_needed(){
  $token = get_site_option('qbnox_oauth_token');
  if(!$token || $token['expires'] > time()+300) return;

  if($token['provider']==='google'){
    $p=new Google([
      'clientId'=>get_site_option('qbnox_google_client'),
      'clientSecret'=>qbnox_decrypt(get_site_option('qbnox_google_secret')),
    ]);
  } else {
    $p=new Azure([
      'clientId'=>get_site_option('qbnox_ms_client'),
      'clientSecret'=>qbnox_decrypt(get_site_option('qbnox_ms_secret')),
      'tenant'=>get_site_option('qbnox_ms_tenant','common'),
    ]);
  }

  $new=$p->getAccessToken('refresh_token',['refresh_token'=>qbnox_decrypt($token['refresh'])]);
  update_site_option('qbnox_oauth_token',[
    'access'=>qbnox_encrypt($new->getToken()),
    'refresh'=>qbnox_encrypt($new->getRefreshToken() ?: qbnox_decrypt($token['refresh'])),
    'expires'=>$new->getExpires(),
    'provider'=>$token['provider']
  ]);
}

add_action('phpmailer_init','qbnox_refresh_token_if_needed',1);
