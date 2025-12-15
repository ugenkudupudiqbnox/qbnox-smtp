<?php
declare(strict_types=1);
class Qbnox_SMTP_OAuth
{
    const CRON_HOOK = 'qbnox_smtp_cron_refresh';

    public static function init(): void
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'cron_refresh']);

        // schedule hourly refresh if not scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'hourly', self::CRON_HOOK);
        }
    }

    public static function cron_refresh(): void
    {
        // Attempt to refresh stored token proactively
        self::refresh_if_needed();
    }
    public static function refresh_if_needed(): ?array
    {
        $oauth = get_site_option('qbnox_smtp_oauth');
        if (empty($oauth['refresh_token'])) {
            return null;
        }
        // If token is still valid for more than 5 minutes, skip refresh
        if (time() < ($oauth['expires_at'] ?? 0) - 300) {
            return $oauth;
        }
        $cfg = get_site_option('qbnox_smtp_oauth_config');
        // decrypt client_secret if stored encrypted
        if (!empty($cfg['client_secret']) && is_string($cfg['client_secret'])) {
            $cfg['client_secret'] = qbnox_decrypt($cfg['client_secret']);
        }
        $endpoint = $oauth['provider']==='google'
          ? 'https://oauth2.googleapis.com/token'
          : 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        // decrypt refresh token if encrypted
        $refresh = $oauth['refresh_token'];
        if (is_string($refresh) && strpos($refresh, 'enc:') === 0) {
            $refresh = qbnox_decrypt($refresh);
        }

        $res = wp_remote_post($endpoint, [
            'body'=>[
                'client_id'=>$cfg['client_id'],
                'client_secret'=>$cfg['client_secret'],
                'refresh_token'=>$refresh,
                'grant_type'=>'refresh_token'
            ]
        ]);

        $code = wp_remote_retrieve_response_code($res);
        $tok = qbnox_json_decode(wp_remote_retrieve_body($res));

        if (!empty($tok['access_token'])) {
            $oauth['access_token']=$tok['access_token'];
            $oauth['expires_at']=time()+$tok['expires_in'];
            update_site_option('qbnox_smtp_oauth', $oauth);
            // clear last error
            delete_site_option('qbnox_smtp_oauth_last_error');
            return $oauth;
        }

        // store last error for admin diagnostics
        update_site_option('qbnox_smtp_oauth_last_error', [
            'time' => current_time('mysql'),
            'http_code' => $code,
            'response' => $tok,
        ]);

        return null;
    }
}
