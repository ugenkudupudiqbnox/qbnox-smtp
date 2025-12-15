<?php
declare(strict_types=1);
class Qbnox_SMTP_Logger
{
    public static function install(): void
    {
        global $wpdb;
        $table = $wpdb->base_prefix.'qbnox_email_logs';
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time DATETIME NOT NULL,
            provider VARCHAR(20) NULL,
            event VARCHAR(50) NOT NULL,
            payload JSON NULL
        )");
    }
    public static function log(string $event, array $payload = [], ?string $provider = null): void
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->base_prefix.'qbnox_email_logs',
            [
                'time'=>current_time('mysql'),
                'provider'=>$provider,
                'event'=>$event,
                'payload'=>wp_json_encode($payload)
            ],
            ['%s','%s','%s','%s']
        );
    }
}
