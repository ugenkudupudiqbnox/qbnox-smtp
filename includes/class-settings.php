<?php
declare(strict_types=1);

class Qbnox_SMTP_Settings
{

    public static function defaults(): array
    {
        return [
            'smtp' => [
                'provider'    => 'custom',
                'host'        => '',
                'port'        => 587,
                'encryption'  => 'tls',
                'username'    => '',
                'password'    => '',
                'from_email'  => '',
                'from_name'   => 'Qbnox Systems',
            ],
            'failover' => [
                'host'       => '',
                'port'       => 587,
                'encryption' => 'tls',
                'username'   => '',
                'password'   => '',
            ],
            'oauth' => [
                'provider'      => '',
                'client_id'     => '',
                'client_secret' => '',
                'email'         => '',
            ],
            'brevo' => [
                'enabled'    => false,
                'method'     => 'api', // 'smtp' or 'api'
                'api_key'    => '',
                'smtp_host'  => 'smtp-relay.brevo.com',
                'smtp_port'  => 587,
                'smtp_user'  => '',
                'smtp_pass'  => '',
                'from_email' => '',
                'from_name'  => '',
            ],
        ];
    }

    /**
     * Always return a valid array, even if DB data is corrupted or legacy.
     */
    public static function get(): array
    {

        $saved = get_site_option('qbnox_smtp_network');

        // Option does not exist
        if ($saved === false) {
            return self::defaults();
        }

        // If stored as JSON string, decode safely
        if (is_string($saved)) {
            $decoded = json_decode($saved, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $saved = $decoded;
            } else {
                // Corrupted value → reset safely
                return self::defaults();
            }
        }

        // Final safety check
        if (!is_array($saved)) {
            return self::defaults();
        }

        return array_replace_recursive(
            self::defaults(),
            $saved
        );
    }

    /**
     * Save settings safely and consistently.
     */
    public static function save(array $data): void
    {
        update_site_option(
            'qbnox_smtp_network',
            array_replace_recursive(
                self::defaults(),
                $data
            )
        );
    }
}
