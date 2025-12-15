<?php
declare(strict_types=1);

class Qbnox_SMTP_Retry {

    public static function run(callable $fn, int $attempts = 3) {
        $delays = [1, 3, 7];
        $last = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                $last = $e;
                sleep($delays[$i] ?? 7);
            }
        }

        throw $last;
    }
}

