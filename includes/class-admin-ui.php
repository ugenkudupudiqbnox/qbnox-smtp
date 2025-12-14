<?php
declare(strict_types=1);

class Qbnox_SMTP_Admin_UI {

    public static function init(): void {
        add_action('network_admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function register_menu(): void {
        add_submenu_page(
            'settings.php',
            'Qbnox SMTP',
            'Qbnox SMTP',
            'manage_network_options',
            'qbnox-smtp',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        echo '<div class="wrap">';
        echo '<h1>Qbnox Systems – Advanced SMTP</h1>';
        echo '<div id="qbnox-smtp-root"></div>';
        echo '</div>';
    }

    public static function enqueue_assets(string $hook): void {

        // Only load on our page
        if ($hook !== 'settings_page_qbnox-smtp') {
            return;
        }

        wp_enqueue_style(
            'qbnox-smtp-admin',
            QBNOX_SMTP_URL . 'assets/admin.css',
            [],
            '2.0.1'
        );

        wp_enqueue_script(
            'qbnox-smtp-admin',
            QBNOX_SMTP_URL . 'assets/admin-app.js',
            ['wp-element', 'wp-api-fetch'],
            '2.0.1',
            true
        );
    }
}

