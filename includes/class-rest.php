<?php
declare(strict_types=1);
class Qbnox_SMTP_REST {
    public static function init(): void {
        add_action('rest_api_init',[__CLASS__,'routes']);
    }
    public static function routes(): void {
        register_rest_route('qbnox-smtp/v1','/analytics',[
            'methods'=>'GET',
            'permission_callback'=>fn()=>current_user_can('manage_network_options'),
            'callback'=>fn()=>{
                global $wpdb;
                return $wpdb->get_results(
                    "SELECT event,COUNT(*) total FROM {$wpdb->base_prefix}qbnox_email_logs GROUP BY event",
                    ARRAY_A
                );
            }
        ]);
        register_rest_route('qbnox-smtp/v1','/webhook/ses',[
            'methods'=>'POST',
            'permission_callback'=>'__return_true',
            'callback'=>['Qbnox_SMTP_Webhooks','ses']
        ]);
        register_rest_route('qbnox-smtp/v1','/webhook/brevo',[
            'methods'=>'POST',
            'permission_callback'=>'__return_true',
            'callback'=>['Qbnox_SMTP_Webhooks','brevo']
        ]);
    }
}
