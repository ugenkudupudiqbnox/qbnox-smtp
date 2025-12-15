<?php
declare(strict_types=1);

class Qbnox_SMTP_OAuth {

    public static function init(): void {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes(): void {

        // Start OAuth
        register_rest_route('qbnox-smtp/v1', '/oauth/start', [
            'methods'             => 'POST',
            'permission_callback' => [__CLASS__, 'permissions'],
            'callback'            => [__CLASS__, 'start'],
        ]);

        // OAuth callback (PUBLIC)
        register_rest_route('qbnox-smtp/v1', '/oauth/callback', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'callback'],
        ]);

        // Disconnect
        register_rest_route('qbnox-smtp/v1', '/oauth/disconnect', [
            'methods'             => 'POST',
            'permission_callback' => [__CLASS__, 'permissions'],
            'callback'            => [__CLASS__, 'disconnect'],
        ]);
    }

    public static function permissions(): bool {
        return is_multisite()
            ? current_user_can('manage_network_options')
            : current_user_can('manage_options');
    }

    public static function start(\WP_REST_Request $req) {

        $provider = $req->get_param('provider');
        if (!in_array($provider, ['google', 'microsoft'], true)) {
            return new \WP_Error('invalid_provider', 'Invalid provider');
        }

        $cfg = Qbnox_SMTP_Settings::get();
        $clientId = $cfg['oauth']['client_id'] ?? '';

        if (!$clientId) {
            return new \WP_Error('missing_client_id', 'Client ID missing');
        }

        $redirectUri = rest_url('qbnox-smtp/v1/oauth/callback');
        $state = wp_generate_uuid4();

        update_site_option('qbnox_smtp_oauth_state', $state);

        if ($provider === 'google') {
            $authUrl = add_query_arg([
                'client_id'     => $clientId,
                'redirect_uri'  => $redirectUri,
                'response_type' => 'code',
                'scope'         => 'https://mail.google.com/',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'state'         => $state,
            ], 'https://accounts.google.com/o/oauth2/v2/auth');
        } else {
            $authUrl = add_query_arg([
                'client_id'     => $clientId,
                'redirect_uri'  => $redirectUri,
                'response_type' => 'code',
                'scope'         => 'https://graph.microsoft.com/.default',
                'state'         => $state,
            ], 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize');
        }

        return rest_ensure_response(['url' => $authUrl]);
    }

    public static function callback(\WP_REST_Request $req) {

        $state = get_site_option('qbnox_smtp_oauth_state');
        if ($state !== $req->get_param('state')) {
            wp_die('Invalid OAuth state');
        }

        $code = $req->get_param('code');
        if (!$code) {
            wp_die('Missing OAuth code');
        }

        // Exchange code → refresh token (provider-specific)
        self::exchange_code($code);

        wp_safe_redirect(
            network_admin_url('settings.php?page=qbnox-smtp&oauth=connected')
        );
        exit;
    }

    private static function exchange_code(string $code): void {

        $cfg = Qbnox_SMTP_Settings::get();
        $provider = $cfg['oauth']['provider'];

        $endpoint = $provider === 'google'
            ? 'https://oauth2.googleapis.com/token'
            : 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

        $response = wp_remote_post($endpoint, [
            'body' => [
                'client_id'     => $cfg['oauth']['client_id'],
                'client_secret' => $cfg['oauth']['client_secret'],
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => rest_url('qbnox-smtp/v1/oauth/callback'),
            ],
        ]);

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['refresh_token'])) {
            wp_die('OAuth token exchange failed');
        }

        update_site_option('qbnox_smtp_oauth_tokens', [
            'provider'      => $provider,
            'refresh_token' => Qbnox_SMTP_Crypto::encrypt($data['refresh_token']),
        ]);
    }

    public static function disconnect() {
        delete_site_option('qbnox_smtp_oauth_tokens');
        return ['success' => true];
    }
}

