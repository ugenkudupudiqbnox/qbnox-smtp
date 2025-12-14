<?php
declare(strict_types=1);
use PHPMailer\PHPMailer\OAuth;
class Qbnox_SMTP_Mailer {
    public static function init(): void {
        add_action('phpmailer_init',[__CLASS__,'configure']);
    }
    public static function configure($phpmailer): void {
        $cfg = Qbnox_SMTP_Settings::get();
        if (empty($cfg['host'])) return;
        $phpmailer->isSMTP();
        $phpmailer->Host=$cfg['host'];
        $phpmailer->Port=$cfg['port'] ?? 587;
        $phpmailer->SMTPAuth=true;
        $phpmailer->Username=$cfg['username'] ?? '';
        $phpmailer->Password=$cfg['password'] ?? '';
        $phpmailer->SMTPSecure=$cfg['encryption'] ?? 'tls';
        $oauth = Qbnox_SMTP_OAuth::refresh_if_needed();
        if ($oauth) {
            $phpmailer->AuthType='XOAUTH2';
            $phpmailer->setOAuth(new OAuth([
                'provider'=>$oauth['provider'],
                'userName'=>$oauth['email'],
                'accessToken'=>$oauth['access_token']
            ]));
        }
    }
}
