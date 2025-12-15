<?php
declare(strict_types=1);
function qbnox_json_decode(string $json): array
{
    try {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return [];
    }
}

function qbnox_crypto_key(): string
{
    // Derive a key from WP salts and site URL
    $salt1 = defined('AUTH_SALT') ? AUTH_SALT : wp_salt('auth');
    $salt2 = defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : wp_salt('secure_auth');
    $site = get_site_url();
    return hash('sha256', $salt1 . '|' . $salt2 . '|' . $site, true);
}

function qbnox_encrypt(string $plain): string
{
    $key = qbnox_crypto_key();
    $iv = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        return $plain;
    }
    return 'enc:' . base64_encode($iv . $cipher);
}

function qbnox_decrypt(string $blob): string
{
    if (strpos($blob, 'enc:') !== 0) {
        return $blob;
    }
    $data = base64_decode(substr($blob, 4));
    if ($data === false || strlen($data) < 17) {
        return '';
    }
    $iv = substr($data, 0, 16);
    $cipher = substr($data, 16);
    $key = qbnox_crypto_key();
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}
