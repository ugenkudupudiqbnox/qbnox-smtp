<?php
declare(strict_types=1);

/**
 * Brevo API Mailer
 * Handles email sending via Brevo (SendinBlue) API
 */
class Qbnox_SMTP_Brevo_API
{
    /**
     * Send email via Brevo API
     */
    public static function send_email($to, $subject, $message, $headers = '', $attachments = []): bool
    {
        $cfg = Qbnox_SMTP_Settings::get();

        if (empty($cfg['brevo']['api_key']) || $cfg['brevo']['method'] !== 'api') {
            return false;
        }

        // Parse recipient
        $recipient = self::parse_recipient($to);
        if (!$recipient) {
            return false;
        }

        // Parse headers
        $parsed_headers = self::parse_headers($headers);

        // Prepare API payload
        $payload = [
            'sender' => [
                'email' => $cfg['brevo']['from_email'] ?: $cfg['smtp']['from_email'],
                'name'  => $cfg['brevo']['from_name'] ?: $cfg['smtp']['from_name'],
            ],
            'to' => [$recipient],
            'subject' => $subject,
        ];

        // Check if HTML or plain text
        if ($parsed_headers['content_type'] === 'text/html') {
            $payload['htmlContent'] = $message;
        } else {
            $payload['textContent'] = $message;
        }

        // Add CC and BCC if present
        if (!empty($parsed_headers['cc'])) {
            $payload['cc'] = array_map([self::class, 'parse_recipient'], $parsed_headers['cc']);
        }
        if (!empty($parsed_headers['bcc'])) {
            $payload['bcc'] = array_map([self::class, 'parse_recipient'], $parsed_headers['bcc']);
        }

        // Send via Brevo API
        $response = wp_remote_post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'accept'       => 'application/json',
                'content-type' => 'application/json',
                'api-key'      => $cfg['brevo']['api_key'],
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('Brevo API Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code === 201) {
            $result = json_decode($body, true);
            error_log('Brevo API Success: Message ID ' . ($result['messageId'] ?? 'unknown'));
            return true;
        } else {
            error_log('Brevo API Failed: ' . $status_code . ' - ' . $body);
            return false;
        }
    }

    /**
     * Parse recipient email
     */
    private static function parse_recipient($email): ?array
    {
        if (is_array($email)) {
            $email = $email[0];
        }

        // Extract name and email from "Name <email@domain.com>" format
        if (preg_match('/^(.+?)\s*<(.+?)>$/', $email, $matches)) {
            return [
                'name'  => trim($matches[1], '"'),
                'email' => trim($matches[2]),
            ];
        }

        // Plain email
        if (is_email($email)) {
            return ['email' => $email];
        }

        return null;
    }

    /**
     * Parse email headers
     */
    private static function parse_headers($headers): array
    {
        $parsed = [
            'content_type' => 'text/plain',
            'cc'           => [],
            'bcc'          => [],
        ];

        if (empty($headers)) {
            return $parsed;
        }

        if (!is_array($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        }

        foreach ($headers as $header) {
            $header = trim($header);
            if (empty($header)) {
                continue;
            }

            list($name, $content) = explode(':', $header, 2);
            $name = strtolower(trim($name));
            $content = trim($content);

            switch ($name) {
                case 'content-type':
                    if (strpos($content, 'text/html') !== false) {
                        $parsed['content_type'] = 'text/html';
                    }
                    break;
                case 'cc':
                    $parsed['cc'][] = $content;
                    break;
                case 'bcc':
                    $parsed['bcc'][] = $content;
                    break;
            }
        }

        return $parsed;
    }
}
