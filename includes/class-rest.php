<?php
declare(strict_types=1);

class Qbnox_SMTP_REST {

    public static function init(): void {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes(): void {

register_rest_route('qbnox-smtp/v1', '/settings', [
    'methods' => ['GET', 'POST'],
    'permission_callback' => function (): bool {
        return current_user_can('manage_network_options');
    },
    'callback' => function (WP_REST_Request $req) {

	if ($req->get_method() === 'POST') {
            Qbnox_SMTP_Settings::save(
                $req->get_json_params()
            );
        }

	return Qbnox_SMTP_Settings::get();
    }
]);


register_rest_route('qbnox-smtp/v1', '/test-mail', [
    'methods' => 'POST',
    'permission_callback' => function (): bool {
        return current_user_can('manage_network_options');
    },
    'callback' => function (): array {

        // Reset last error
        delete_site_option('qbnox_smtp_last_error');

        $to = get_site_option('admin_email');
        $from = $to;
        $subject = 'Qbnox SMTP Test (API)';
        $body = 'Test email sent using OAuth API';

        $settings = Qbnox_SMTP_Settings::get();
        $provider = $settings['oauth']['provider'] ?? null;

        try {

            if ($provider === 'google') {

                Qbnox_SMTP_Gmail_API::send_test(
                    $to,
                    $subject,
                    $body,
                    $from
                );

                return [
                    'status'    => 'success',
                    'transport' => 'gmail-api',
                    'to'        => $to,
                ];

            } elseif ($provider === 'microsoft') {

                Qbnox_SMTP_MSGraph_API::send_test(
                    $to,
                    $subject,
                    $body,
                    $from
                );

                return [
                    'status'    => 'success',
                    'transport' => 'msgraph-api',
                    'to'        => $to,
                ];
            }

            return [
                'status'  => 'error',
                'message' => 'OAuth provider not configured',
            ];

        } catch (\Throwable $e) {

            update_site_option('qbnox_smtp_last_error', [
                'message' => $e->getMessage(),
                'time'    => time(),
            ]);

            return [
                'status'  => 'error',
                'to'      => $to,
                'message' => $e->getMessage(),
            ];
        }
    }
]);

        register_rest_route('qbnox-smtp/v1', '/analytics', [
            'methods'  => 'GET',
            'permission_callback' => function (): bool {
                return current_user_can('manage_network_options');
            },
            'callback' => function (): array {
                global $wpdb;

                $table = $wpdb->base_prefix . 'qbnox_email_logs';

                $results = $wpdb->get_results(
                    "SELECT event, COUNT(*) AS total
                     FROM {$table}
                     GROUP BY event",
                    ARRAY_A
                );

                return $results ?: [];
            }
        ]);

        register_rest_route('qbnox-smtp/v1', '/webhook/ses', [
            'methods'  => 'POST',
            'permission_callback' => '__return_true',
            'callback' => ['Qbnox_SMTP_Webhooks', 'ses']
        ]);

        register_rest_route('qbnox-smtp/v1', '/webhook/brevo', [
            'methods'  => 'POST',
            'permission_callback' => '__return_true',
            'callback' => ['Qbnox_SMTP_Webhooks', 'brevo']
        ]);
    }
}

