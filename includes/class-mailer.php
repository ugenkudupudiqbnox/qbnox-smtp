<?php
declare(strict_types=1);

class Qbnox_SMTP_Mailer
{

    /**
     * Register PHPMailer configuration hook
     */
    public static function init(): void
    {
        // Check if Brevo API is enabled
        $cfg = Qbnox_SMTP_Settings::get();

        if (!empty($cfg['brevo']['enabled']) && $cfg['brevo']['method'] === 'api' && !empty($cfg['brevo']['api_key'])) {
            // Use Brevo API instead of PHPMailer
            add_filter('pre_wp_mail', [__CLASS__, 'brevo_api_mail'], 10, 2);
        } else {
            // Use traditional SMTP
            add_action('phpmailer_init', [__CLASS__, 'configure']);
        }
    }

    /**
     * Intercept wp_mail and use Brevo API
     */
    public static function brevo_api_mail($null, $atts)
    {
        $result = Qbnox_SMTP_Brevo_API::send_email(
            $atts['to'],
            $atts['subject'],
            $atts['message'],
            $atts['headers'],
            $atts['attachments']
        );

        // Return true to prevent wp_mail from running
        // Return null to let wp_mail run normally (fallback)
        return $result ? true : null;
    }

    /**
     * Configure SMTP settings (for SMTP method or Brevo SMTP)
     */
    public static function configure($phpmailer): void
    {
        $cfg = Qbnox_SMTP_Settings::get();

        // Check if Brevo SMTP is enabled
        if (!empty($cfg['brevo']['enabled']) && $cfg['brevo']['method'] === 'smtp') {
            $phpmailer->isSMTP();
            $phpmailer->Host       = $cfg['brevo']['smtp_host'] ?? 'smtp-relay.brevo.com';
            $phpmailer->Port       = (int) ($cfg['brevo']['smtp_port'] ?? 587);
            $phpmailer->SMTPAuth   = true;
            $phpmailer->Username   = $cfg['brevo']['smtp_user'] ?? '';
            $phpmailer->Password   = $cfg['brevo']['smtp_pass'] ?? '';
            $phpmailer->SMTPSecure = 'tls';

            if (!empty($cfg['brevo']['from_email'])) {
                $phpmailer->setFrom(
                    $cfg['brevo']['from_email'],
                    $cfg['brevo']['from_name'] ?? 'Qbnox Systems'
                );
            }
            return;
        }

        // Standard SMTP configuration
        if (empty($cfg['smtp']['host'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $cfg['smtp']['host'];
        $phpmailer->Port       = (int) ($cfg['smtp']['port'] ?? 587);
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
