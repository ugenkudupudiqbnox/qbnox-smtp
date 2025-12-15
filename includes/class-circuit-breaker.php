<?php
declare(strict_types=1);

class Qbnox_SMTP_Circuit_Breaker {

    private const THRESHOLD = 5;
    private const COOLDOWN  = 600; // seconds

    public static function allow(string $provider): bool {
        $state = get_site_option("qbnox_smtp_cb_$provider");
        if (!$state) {
            return true;
        }

        if (($state['open'] ?? false) === true) {
            if (time() - ($state['opened_at'] ?? 0) > self::COOLDOWN) {
                delete_site_option("qbnox_smtp_cb_$provider");
                return true;
            }
            return false;
        }

        return true;
    }

    public static function success(string $provider): void {
        delete_site_option("qbnox_smtp_cb_$provider");
    }

    public static function failure(string $provider): void {
        $state = get_site_option("qbnox_smtp_cb_$provider", [
            'failures' => 0,
            'open'     => false,
        ]);

        $state['failures']++;

        if ($state['failures'] >= self::THRESHOLD) {
            $state['open'] = true;
            $state['opened_at'] = time();
        }

        update_site_option("qbnox_smtp_cb_$provider", $state);
    }
}

