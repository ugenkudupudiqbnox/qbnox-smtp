<?php
declare(strict_types=1);
use PHPMailer\PHPMailer\OAuth;
class Qbnox_SMTP_Mailer {
    public static function init(): void {
        add_action('phpmailer_init',[__CLASS__,'configure']);
    }
    public static function configure($phpmailer): void {

    $cfg = get_site_option('qbnox_smtp_network', []);
    if (empty($cfg['smtp']['host'])) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $cfg['smtp']['host'];
    $phpmailer->Port       = (int)($cfg['smtp']['port'] ?? 587);
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $cfg['smtp']['username'] ?? '';
    $phpmailer->Password   = $cfg['smtp']['password'] ?? '';
    $phpmailer->SMTPSecure = $cfg['smtp']['encryption'] ?? 'tls';

    if (!empty($cfg['smtp']['from_email'])) {
        $phpmailer->setFrom(
            $cfg['smtp']['from_email'],
            $cfg['smtp']['from_name'] ?? 'Qbnox Systems'
        );
    }
}

}
