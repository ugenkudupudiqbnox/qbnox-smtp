<?php
declare(strict_types=1);

class Qbnox_SMTP_Mailer
{
    /**
     * When true, skip async enqueue and API short-circuit to allow synchronous send.
     * Used internally by the send worker.
     */
    public static bool $skip_async = false;

    /**
     * Register PHPMailer configuration hook
     */
    public static function init(): void
    {
        add_action('phpmailer_init', [__CLASS__, 'configure']);
        // Attempt API send (OAuth) before falling back to PHPMailer SMTP
        add_filter('pre_wp_mail', [__CLASS__, 'maybe_api_send'], 10, 2);
    }

    /**
     * Configure SMTP settings
     */
    public static function configure($phpmailer): void
    {

        $cfg = Qbnox_SMTP_Settings::get();

        if (empty($cfg['smtp']['host'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $cfg['smtp']['host'];
        $phpmailer->Port       = (int) ($cfg['smtp']['port'] ?? 587);
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $cfg['smtp']['username'] ?? '';
        $pwd = $cfg['smtp']['password'] ?? '';
        if (is_string($pwd) && strpos($pwd, 'enc:') === 0) {
            $pwd = qbnox_decrypt($pwd);
        }
        $phpmailer->Password   = $pwd ?? '';
        $phpmailer->SMTPSecure = $cfg['smtp']['encryption'] ?? 'tls';

        if (!empty($cfg['smtp']['from_email'])) {
            $phpmailer->setFrom(
                $cfg['smtp']['from_email'],
                $cfg['smtp']['from_name'] ?? 'Qbnox Systems'
            );
        }
    }

    /**
     * Attempt to send mail via provider API (Google Workspace / Microsoft 365).
     * Return non-null to short-circuit WP mail when sent successfully.
     * This method will call the OAuth refresher to ensure tokens are current.
     *
     * @param null|mixed $pre
     * @param array $atts WP Mail attributes: to, subject, message, headers, attachments
     * @return null|bool
     */
    public static function maybe_api_send($pre, array $atts)
    {
        // If we're already in the worker, don't async-enqueue
        if (self::$skip_async) {
            return null;
        }

        // Enqueue an async send via a non-blocking request to our REST worker.
        $endpoint = rest_url('qbnox-smtp/v1/send-async');
        $secret = get_site_option('qbnox_smtp_worker_secret');
        $body = wp_json_encode($atts);
        $ts = (string) time();
        $nonce = bin2hex(random_bytes(8));
        $headers = [ 'Content-Type' => 'application/json' ];
        if (!empty($secret)) {
            $sig = hash_hmac('sha256', $body . '|' . $ts . '|' . $nonce, (string) $secret);
            $headers['X-QBNOX-TS'] = $ts;
            $headers['X-QBNOX-SIGN'] = $sig;
            $headers['X-QBNOX-NONCE'] = $nonce;
        }

        $args = [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 0.01,
            'blocking' => false,
        ];

        // fire-and-forget
        wp_remote_post($endpoint, $args);

        // Short-circuit wp_mail: treat as accepted for processing asynchronously
        return true;
    }

    private static function send_via_gmail_api(string $access_token, array $atts): bool
    {
        $to = is_array($atts['to'] ?? null) ? $atts['to'] : explode(',', (string)($atts['to'] ?? ''));
        $subject = $atts['subject'] ?? '';
        $message = $atts['message'] ?? '';
        $headers = $atts['headers'] ?? [];

        $from = '';
        // try to get from header or settings
        foreach ((array)$headers as $h) {
            if (stripos($h, 'From:') === 0) { $from = trim(substr($h, 5)); break; }
        }
        if (empty($from)) {
            $cfg = Qbnox_SMTP_Settings::get();
            $from = $cfg['smtp']['from_email'] ?? ($cfg['oauth']['email'] ?? '');
        }

        $raw = "From: {$from}\r\n";
        $raw .= "To: " . implode(', ', $to) . "\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "MIME-Version: 1.0\r\n";
        $raw .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $raw .= $message;

        $rawb64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $endpoint = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode(['raw' => $rawb64]),
            'timeout' => 15,
        ];

        $res = wp_remote_post($endpoint, $args);
        $code = wp_remote_retrieve_response_code($res);
        return ($code >= 200 && $code < 300);
    }

    private static function send_via_graph_api(string $access_token, array $atts, ?string $from_email): bool
    {
        $to = is_array($atts['to'] ?? null) ? $atts['to'] : explode(',', (string)($atts['to'] ?? ''));
        $subject = $atts['subject'] ?? '';
        $message = $atts['message'] ?? '';
        $headers = $atts['headers'] ?? [];

        $from = $from_email;
        foreach ((array)$headers as $h) {
            if (stripos($h, 'From:') === 0) { $from = trim(substr($h, 5)); break; }
        }
        if (empty($from)) {
            $cfg = Qbnox_SMTP_Settings::get();
            $from = $cfg['smtp']['from_email'] ?? '';
        }

        $recips = [];
        foreach ($to as $t) {
            $t = trim($t);
            if ($t === '') { continue; }
            $recips[] = ['emailAddress' => ['address' => $t]];
        }

        $bodyType = (stripos((string)$headers, 'text/html') !== false) ? 'HTML' : 'Text';

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => $bodyType,
                    'content' => $message,
                ],
                'toRecipients' => $recips,
                'from' => ['emailAddress' => ['address' => $from]],
            ],
            'saveToSentItems' => true,
        ];

        // send as the configured user if provided
        $endpointBase = 'https://graph.microsoft.com/v1.0';
        $endpoint = $endpointBase . '/me/sendMail';
        if (!empty($from_email)) {
            // use user endpoint so sending as that mailbox
            $endpoint = $endpointBase . '/users/' . rawurlencode($from_email) . '/sendMail';
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode($payload),
            'timeout' => 15,
        ];

        $res = wp_remote_post($endpoint, $args);
        $code = wp_remote_retrieve_response_code($res);
        return ($code >= 200 && $code < 300);
    }
}
