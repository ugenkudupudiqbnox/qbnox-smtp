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

        // OAuth start (returns authorization URL)
        register_rest_route('qbnox-smtp/v1', '/oauth/start', [
            'methods' => 'POST',
            'permission_callback' => function (): bool {
                return current_user_can('manage_network_options');
            },
            'callback' => function (WP_REST_Request $req) {
                $body = $req->get_json_params();
                $provider = $body['provider'] ?? '';
                $settings = Qbnox_SMTP_Settings::get();
                $cfg = $settings['oauth'] ?? [];

                $client_id = $cfg['client_id'] ?? '';
                $redirect = rest_url('qbnox-smtp/v1/oauth/callback');
                $state = wp_create_nonce('qbnox_smtp_oauth');
                set_transient('qbnox_smtp_oauth_state_' . $state, true, 300);

                if ($provider === 'google') {
                    $scope = urlencode('https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email openid');
                    $url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$client_id}&redirect_uri=" . rawurlencode($redirect) . "&response_type=code&scope={$scope}&access_type=offline&prompt=consent&state={$state}";
                } else {
                    // microsoft
                    $scope = rawurlencode('offline_access Mail.Send openid email');
                    $url = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id={$client_id}&response_type=code&redirect_uri=" . rawurlencode($redirect) . "&response_mode=query&scope={$scope}&state={$state}";
                }

                return ['url' => $url, 'state' => $state];
            }
        ]);

        // OAuth callback (exchange code -> tokens)
        register_rest_route('qbnox-smtp/v1', '/oauth/callback', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $req) {
                $code = $req->get_param('code');
                $state = $req->get_param('state');
                $provider = $req->get_param('provider') ?? '';

                if (empty($code) || empty($state) || !get_transient('qbnox_smtp_oauth_state_' . $state)) {
                    return new WP_Error('invalid_request', 'Missing or invalid state/code', ['status' => 400]);
                }
                // remove transient
                delete_transient('qbnox_smtp_oauth_state_' . $state);

                $settings = Qbnox_SMTP_Settings::get();
                $cfg = $settings['oauth'] ?? [];
                $client_id = $cfg['client_id'] ?? '';
                $client_secret = $cfg['client_secret'] ?? '';
                $redirect = rest_url('qbnox-smtp/v1/oauth/callback');

                if (strpos($_SERVER['REQUEST_URI'], '/oauth/callback') === false) {
                    // ensure redirect_uri matches
                    $redirect = rest_url('qbnox-smtp/v1/oauth/callback');
                }

                if (strpos($req->get_header('host') ?? '', 'localhost') !== false) {
                    // nothing special; keep redirect
                }

                if ($provider === 'google') {
                    $token_url = 'https://oauth2.googleapis.com/token';
                    $body = [
                        'code' => $code,
                        'client_id' => $client_id,
                        'client_secret' => $client_secret,
                        'redirect_uri' => $redirect,
                        'grant_type' => 'authorization_code',
                    ];
                } else {
                    $token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
                    $body = [
                        'client_id' => $client_id,
                        'client_secret' => $client_secret,
                        'code' => $code,
                        'redirect_uri' => $redirect,
                        'grant_type' => 'authorization_code',
                    ];
                }

                $res = wp_remote_post($token_url, ['body' => $body, 'timeout' => 15]);
                $code_r = wp_remote_retrieve_response_code($res);
                $tok = qbnox_json_decode(wp_remote_retrieve_body($res));

                if ($code_r < 200 || $code_r >= 300 || empty($tok['access_token'])) {
                    return new WP_Error('token_error', 'Failed to retrieve tokens', ['status' => 400, 'response' => $tok]);
                }

                $email = null;
                if (!empty($tok['id_token'])) {
                    $parts = explode('.', $tok['id_token']);
                    if (!empty($parts[1])) {
                        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        if (!empty($payload['email'])) { $email = $payload['email']; }
                        if (empty($email) && !empty($payload['upn'])) { $email = $payload['upn']; }
                    }
                }

                $oauth = [
                    'provider' => $provider === 'google' ? 'google' : 'microsoft',
                    'access_token' => qbnox_encrypt((string)($tok['access_token'] ?? '')),
                    'refresh_token' => !empty($tok['refresh_token']) ? qbnox_encrypt((string)$tok['refresh_token']) : ($tok['refresh_token'] ?? null),
                    'expires_at' => time() + ($tok['expires_in'] ?? 3600),
                    'email' => $email,
                ];

                update_site_option('qbnox_smtp_oauth', $oauth);
                // store client_secret encrypted
                update_site_option('qbnox_smtp_oauth_config', ['client_id' => $client_id, 'client_secret' => qbnox_encrypt((string)$client_secret)]);

                return ['status' => 'ok', 'email' => $email];
            }
        ]);

        // OAuth status
        register_rest_route('qbnox-smtp/v1', '/oauth/status', [
            'methods' => 'GET',
            'permission_callback' => function (): bool {
                return current_user_can('manage_network_options');
            },
            'callback' => function () {
                $oauth = get_site_option('qbnox_smtp_oauth');
                $cfg = get_site_option('qbnox_smtp_oauth_config');
                $last = get_site_option('qbnox_smtp_oauth_last_error');
                return ['oauth' => $oauth ?: null, 'config' => $cfg ?: null, 'last_error' => $last ?: null];
            }
        ]);

        // OAuth disconnect (clear tokens)
        register_rest_route('qbnox-smtp/v1', '/oauth/disconnect', [
            'methods' => 'POST',
            'permission_callback' => function (): bool {
                return current_user_can('manage_network_options');
            },
            'callback' => function () {
                delete_site_option('qbnox_smtp_oauth');
                delete_site_option('qbnox_smtp_oauth_config');
                return ['status' => 'ok'];
            }
        ]);

        // Async send worker endpoint (invoked by plugin via non-blocking request)
        register_rest_route('qbnox-smtp/v1', '/send-async', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $req) {
                // Verify worker HMAC signature, timestamp and nonce. Support secret rotation.
                $secrets = get_site_option('qbnox_smtp_worker_secrets');
                if (empty($secrets) || !is_array($secrets)) {
                    // fallback to legacy single secret
                    $legacy = get_site_option('qbnox_smtp_worker_secret');
                    $secrets = $legacy ? ['current' => $legacy] : [];
                }

                $ts = $req->get_header('x-qbnox-ts') ?? '';
                $sig = $req->get_header('x-qbnox-sign') ?? '';
                $nonce = $req->get_header('x-qbnox-nonce') ?? '';
                if (empty($ts) || empty($sig) || empty($nonce)) {
                    Qbnox_SMTP_Logger::log('worker_auth_fail', ['reason' => 'missing_headers']);
                    return new WP_Error('forbidden', 'Missing worker auth', ['status' => 403]);
                }

                if (abs(time() - (int)$ts) > 120) {
                    Qbnox_SMTP_Logger::log('worker_auth_fail', ['reason' => 'stale_ts', 'ts' => $ts]);
                    return new WP_Error('forbidden', 'Stale worker timestamp', ['status' => 403]);
                }

                // Prevent nonce reuse (replay protection)
                if (get_transient('qbnox_smtp_nonce_' . $nonce)) {
                    Qbnox_SMTP_Logger::log('worker_auth_fail', ['reason' => 'nonce_reuse', 'nonce' => $nonce]);
                    return new WP_Error('forbidden', 'Nonce already used', ['status' => 403]);
                }

                $body = $req->get_body();
                $valid = false;
                foreach ($secrets as $s) {
                    if (empty($s)) { continue; }
                    $expected = hash_hmac('sha256', $body . '|' . $ts . '|' . $nonce, (string)$s);
                    if (hash_equals($expected, (string)$sig)) { $valid = true; break; }
                }
                if (!$valid) {
                    Qbnox_SMTP_Logger::log('worker_auth_fail', ['reason' => 'invalid_sig']);
                    return new WP_Error('forbidden', 'Invalid worker signature', ['status' => 403]);
                }

                // Mark nonce used for 5 minutes
                set_transient('qbnox_smtp_nonce_' . $nonce, 1, 300);

                // Simple rate limit (per-site): max 60 async sends per minute
                $count = (int) get_site_transient('qbnox_smtp_rate_minute') ?: 0;
                if ($count > 60) {
                    Qbnox_SMTP_Logger::log('rate_limited', ['count' => $count]);
                    return new WP_Error('too_many_requests', 'Rate limit exceeded', ['status' => 429]);
                }
                set_site_transient('qbnox_smtp_rate_minute', $count + 1, 60);

                $params = $req->get_json_params();

                Qbnox_SMTP_Logger::log('send_async_received', ['to' => $params['to'] ?? null]);

                // Ensure tokens refreshed before send
                Qbnox_SMTP_OAuth::refresh_if_needed();

                $oauth = get_site_option('qbnox_smtp_oauth');

                // Try API send if oauth configured (decrypt tokens first)
                if (!empty($oauth['access_token']) && !empty($oauth['provider'])) {
                    $provider = $oauth['provider'];
                    $access_token = $oauth['access_token'];
                    if (is_string($access_token) && strpos($access_token, 'enc:') === 0) {
                        $access_token = qbnox_decrypt($access_token);
                    }
                    if ($provider === 'google') {
                        $ok = Qbnox_SMTP_Mailer::send_via_gmail_api($access_token, $params);
                        if ($ok) { Qbnox_SMTP_Logger::log('sent', ['method'=>'google','to'=>$params['to'] ?? null]); return ['status' => 'sent', 'method' => 'google']; }
                    } else {
                        $ok = Qbnox_SMTP_Mailer::send_via_graph_api($access_token, $params, $oauth['email'] ?? null);
                        if ($ok) { Qbnox_SMTP_Logger::log('sent', ['method'=>'microsoft','to'=>$params['to'] ?? null]); return ['status' => 'sent', 'method' => 'microsoft']; }
                    }
                }

                // Fallback to SMTP via synchronous wp_mail call (bypass async short-circuit)
                Qbnox_SMTP_Mailer::$skip_async = true;
                $to = $params['to'] ?? '';
                $subject = $params['subject'] ?? '';
                $message = $params['message'] ?? '';
                $headers = $params['headers'] ?? [];
                $attachments = $params['attachments'] ?? [];

                wp_mail($to, $subject, $message, $headers, $attachments);

                Qbnox_SMTP_Mailer::$skip_async = false;

                $error = get_site_option('qbnox_smtp_last_error');
                if (!empty($error)) {
                    return ['status' => 'error', 'message' => $error['message']];
                }

                return ['status' => 'sent', 'method' => 'smtp'];
            }
        ]);

        // Synchronous test-send for admins (returns which method was used)
        register_rest_route('qbnox-smtp/v1', '/test-send', [
            'methods' => 'POST',
            'permission_callback' => function (): bool {
                return current_user_can('manage_network_options');
            },
            'callback' => function (WP_REST_Request $req) {
                // Accept optional overrides in body, otherwise use admin_email
                $params = $req->get_json_params();
                $to = $params['to'] ?? get_site_option('admin_email');
                $subject = $params['subject'] ?? 'Qbnox SMTP Test (verbose)';
                $message = $params['message'] ?? 'This is a diagnostic test email.';

                // Refresh tokens proactively
                Qbnox_SMTP_OAuth::refresh_if_needed();
                $oauth = get_site_option('qbnox_smtp_oauth');

                if (!empty($oauth['access_token']) && !empty($oauth['provider'])) {
                    $provider = $oauth['provider'];
                    $access_token = $oauth['access_token'];
                    if ($provider === 'google') {
                        $ok = Qbnox_SMTP_Mailer::send_via_gmail_api($access_token, ['to' => [$to], 'subject' => $subject, 'message' => $message]);
                        if ($ok) { return ['status' => 'sent', 'method' => 'google']; }
                    } else {
                        $ok = Qbnox_SMTP_Mailer::send_via_graph_api($access_token, ['to' => [$to], 'subject' => $subject, 'message' => $message], $oauth['email'] ?? null);
                        if ($ok) { return ['status' => 'sent', 'method' => 'microsoft']; }
                    }
                }

                // Fallback to SMTP
                Qbnox_SMTP_Mailer::$skip_async = true;
                wp_mail($to, $subject, $message);
                Qbnox_SMTP_Mailer::$skip_async = false;

                $error = get_site_option('qbnox_smtp_last_error');
                if (!empty($error)) {
                    return ['status' => 'error', 'method' => 'smtp', 'message' => $error['message']];
                }

                return ['status' => 'sent', 'method' => 'smtp'];
            }
        ]);
    }
}
