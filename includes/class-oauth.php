<?php
declare(strict_types=1);
class Qbnox_SMTP_OAuth
{
    public static function refresh_if_needed(): ?array
    {
        $oauth = get_site_option('qbnox_smtp_oauth');
        if (empty($oauth['refresh_token'])) {
            return null;
        }
        if (time() < ($oauth['expires_at'] ?? 0) - 60) {
            return $oauth;
        }
        $cfg = get_site_option('qbnox_smtp_oauth_config');
        $endpoint = $oauth['provider']==='google'
          ? 'https://oauth2.googleapis.com/token'
          : 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $res = wp_remote_post($endpoint, [
            'body'=>[
                'client_id'=>$cfg['client_id'],
                'client_secret'=>$cfg['client_secret'],
                'refresh_token'=>$oauth['refresh_token'],
                'grant_type'=>'refresh_token'
            ]
        ]);
        $tok = qbnox_json_decode(wp_remote_retrieve_body($res));
        if (!empty($tok['access_token'])) {
            $oauth['access_token']=$tok['access_token'];
            $oauth['expires_at']=time()+$tok['expires_in'];
            update_site_option('qbnox_smtp_oauth', $oauth);
            return $oauth;
        }
        return null;
    }
}
