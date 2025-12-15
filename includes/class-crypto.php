<?php
declare(strict_types=1);

class Qbnox_SMTP_Crypto {

    private static function key(): string {
        return hash('sha256', wp_salt('auth'));
    }

    public static function encrypt(string $plain): array {

        $iv = random_bytes(16);
        $cipher = openssl_encrypt(
            $plain,
            'AES-256-CBC',
            self::key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return [
            'iv'   => base64_encode($iv),
            'data' => base64_encode($cipher),
        ];
    }

    public static function decrypt(array $payload): ?string {

        if (empty($payload['iv']) || empty($payload['data'])) {
            return null;
        }

        return openssl_decrypt(
            base64_decode($payload['data']),
            'AES-256-CBC',
            self::key(),
            OPENSSL_RAW_DATA,
            base64_decode($payload['iv'])
        ) ?: null;
    }
}

