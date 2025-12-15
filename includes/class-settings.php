<?php
declare(strict_types=1);

class Qbnox_SMTP_Settings
{

    public static function defaults(): array
    {
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
            // Decrypt any encrypted fields
            self::maybe_decrypt_saved($saved)
        );
    }

    private static function maybe_decrypt_saved(array $saved): array
    {
        if (!empty($saved['smtp']['password']) && is_string($saved['smtp']['password'])) {
            $saved['smtp']['password'] = qbnox_decrypt($saved['smtp']['password']);
        }
        if (!empty($saved['failover']['password']) && is_string($saved['failover']['password'])) {
            $saved['failover']['password'] = qbnox_decrypt($saved['failover']['password']);
        }
        if (!empty($saved['oauth']['client_secret']) && is_string($saved['oauth']['client_secret'])) {
            $saved['oauth']['client_secret'] = qbnox_decrypt($saved['oauth']['client_secret']);
        }
        return $saved;
    }

    /**
     * Save settings safely and consistently.
     */
    public static function save(array $data): void
    {
        // Encrypt sensitive fields before storing
        $store = $data;
        if (!empty($store['smtp']['password'])) {
            $store['smtp']['password'] = qbnox_encrypt((string)$store['smtp']['password']);
        }
        if (!empty($store['failover']['password'])) {
            $store['failover']['password'] = qbnox_encrypt((string)$store['failover']['password']);
        }
        if (!empty($store['oauth']['client_secret'])) {
            $store['oauth']['client_secret'] = qbnox_encrypt((string)$store['oauth']['client_secret']);
        }

        update_site_option(
            'qbnox_smtp_network',
            array_replace_recursive(
                self::defaults(),
                $store
            )
        );
    }
}
