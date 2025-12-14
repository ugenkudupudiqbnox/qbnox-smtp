<?php
declare(strict_types=1);

class Qbnox_SMTP_Settings {

    public static function defaults(): array {
        return [
            'smtp' => [
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
        ];
    }

    public static function get(): array {
        $saved = get_site_option('qbnox_smtp_network');

        if ($saved === false) {
            return self::defaults();
        }

        return array_replace_recursive(self::defaults(), $saved);
    }

    public static function save(array $data): void {
        update_site_option(
            'qbnox_smtp_network',
            array_replace_recursive(self::defaults(), $data)
        );
    }
}

