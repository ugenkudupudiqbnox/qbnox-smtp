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

        wp_mail(
            get_site_option('admin_email'),
            'Qbnox SMTP Test',
            'Test email from Qbnox Systems SMTP plugin.'
        );

        return ['status' => 'sent'];
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

