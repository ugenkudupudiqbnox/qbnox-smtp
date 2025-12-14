<?php
declare(strict_types=1);
class Qbnox_SMTP_Webhooks {
    public static function ses(WP_REST_Request $r): array {
        $data = qbnox_json_decode($r->get_body());
        Qbnox_SMTP_Logger::log($data['Type'] ?? 'ses_event', $data, 'ses');
        return ['status'=>'ok'];
    }
    public static function brevo(WP_REST_Request $r): array {
        $sig = $r->get_header('x-brevo-signature') ?? '';
        $secret = get_site_option('qbnox_brevo_webhook_secret','');
        if (!hash_equals(hash_hmac('sha256',$r->get_body(),$secret),$sig)) {
            return ['status'=>'invalid_signature'];
        }
        $data = qbnox_json_decode($r->get_body());
        Qbnox_SMTP_Logger::log($data['event'] ?? 'brevo_event',$data,'brevo');
        return ['status'=>'ok'];
    }
}
