<?php
declare(strict_types=1);

class Qbnox_SMTP_REST
{

    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes(): void
    {

        register_rest_route('qbnox-smtp/v1', '/settings', [
        'methods' => ['GET', 'POST'],
        'permission_callback' => function (): bool {
            return current_user_can('manage_network_options');
        },
        'callback' => function (WP_REST_Request $req) {

            if ($req->get_method() === 'POST') {
                $data = $req->get_json_params();

                // Enable Brevo if provider is brevo
                if (isset($data['smtp']['provider']) && $data['smtp']['provider'] === 'brevo') {
                    if (!isset($data['brevo'])) {
                        $data['brevo'] = [];
                    }
                    $data['brevo']['enabled'] = true;

                    // Default to API method if not set
                    if (!isset($data['brevo']['method'])) {
                        $data['brevo']['method'] = 'api';
                    }
                }

                // Save API key to api.txt if provided
                if (isset($data['brevo']['api_key']) && !empty($data['brevo']['api_key'])) {
                    $api_file = dirname(QBNOX_SMTP_PATH, 2) . '/api.txt';
                    $content = "Brevo API Key Reference\n";
                    $content .= "=======================\n\n";
                    $content .= "Service: Brevo (SendinBlue)\n";
                    $content .= "Method: " . ($data['brevo']['method'] ?? 'api') . "\n";
                    $content .= "API Key: " . $data['brevo']['api_key'] . "\n\n";
                    $content .= "Configuration Details:\n";
                    $content .= "- From Email: " . ($data['brevo']['from_email'] ?? 'noreply@braou.ac.in') . "\n";
                    $content .= "- From Name: " . ($data['brevo']['from_name'] ?? 'BRAOU iBOOKS') . "\n\n";
                    $content .= "Last Updated: " . date('Y-m-d H:i:s') . "\n";
                    $content .= "Plugin: Qbnox SMTP v" . QBNOX_SMTP_VERSION . "\n\n";
                    $content .= "Note: Keep this file secure and do not commit to version control.\n";

                    file_put_contents($api_file, $content);
                    chmod($api_file, 0600);
                }

                Qbnox_SMTP_Settings::save($data);
            }

            $settings = Qbnox_SMTP_Settings::get();
            // Add admin email to response
            $settings['admin_email'] = get_site_option('admin_email');

            return $settings;
        }
        ]);

        register_rest_route('qbnox-smtp/v1', '/test-mail', [
        'methods' => 'POST',
        'permission_callback' => function (): bool {
            return current_user_can('manage_network_options');
        },
        'callback' => function (WP_REST_Request $req): array {

            // Reset last error
            delete_site_option('qbnox_smtp_last_error');

            // Get custom test email or use default admin email
            $data = $req->get_json_params();
            $custom_email = $data['test_email'] ?? '';

            // Validate custom email if provided
            if (!empty($custom_email) && !is_email($custom_email)) {
                return [
                    'status'  => 'error',
                    'to'      => $custom_email,
                    'message' => 'Invalid email address provided',
                ];
            }

            $to = !empty($custom_email) ? $custom_email : get_site_option('admin_email');

            wp_mail(
                $to,
                'Qbnox SMTP Test',
                'Test email from Qbnox Systems SMTP plugin.'
            );

            $error = get_site_option('qbnox_smtp_last_error');

            if (!empty($error)) {
                return [
                'status'  => 'error',
                'to'      => $to,
                'message' => $error['message'],
                ];
            }

            return [
            'status'  => 'success',
            'to'      => $to,
            'message' => 'Message accepted by WordPress mailer',
            ];
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
