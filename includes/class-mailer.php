<?php
declare(strict_types=1);

class Qbnox_SMTP_Mailer {

    /**
     * Register PHPMailer configuration hook
     */
    public static function init(): void {
        add_action('phpmailer_init', [__CLASS__, 'configure']);
    }

    /**
     * Configure SMTP settings
     */
    public static function configure($phpmailer): void {

        $cfg = Qbnox_SMTP_Settings::get();

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

    public static function send(
    string $to,
    string $subject,
    string $html,
    string $headers = ''
    ): bool {

	    $settings = Qbnox_SMTP_Settings::get();

	    // API MODE (opt-in)
	    if (($settings['mail_mode'] ?? 'smtp') === 'oauth') {

		    $identity = get_site_option('qbnox_smtp_oauth_identity');
		    if (empty($identity['email'])) {
			    throw new \Exception('OAuth sender identity missing');
		    }

		    $from = $identity['email'];

		    if ($identity['provider'] === 'google') {
			    return Qbnox_SMTP_Gmail_API::send_test(
				    $to, $subject, $html, $from
			    );
		    }

		    if ($identity['provider'] === 'microsoft') {
			    return Qbnox_SMTP_MSGraph_API::send_test(
				    $to, $subject, $html, $from
			    );
		    }

		    throw new \Exception('Unsupported OAuth provider');
	    }

	    // SMTP MODE (default)
	    return wp_mail($to, $subject, $html, $headers);
    }
}

