<?php
declare(strict_types=1);
class Qbnox_SMTP_Settings {
    public static function get(): array {
        return get_site_option('qbnox_smtp_network', []);
    }
}
