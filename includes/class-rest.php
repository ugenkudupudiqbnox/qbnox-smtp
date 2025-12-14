<?php
declare(strict_types=1);
class Qbnox_SMTP_REST { public static function init(): void { add_action('rest_api_init',[__CLASS__,'routes']); }
public static function routes(): void {} }