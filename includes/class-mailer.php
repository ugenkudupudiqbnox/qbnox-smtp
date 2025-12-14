<?php
declare(strict_types=1);
class Qbnox_SMTP_Mailer { public static function init(): void { add_action('phpmailer_init',[__CLASS__,'cfg']); }
public static function cfg($m): void { $c=get_site_option('qbnox_smtp_network',[]); if(empty($c['host'])) return; $m->isSMTP(); $m->Host=$c['host']; }}