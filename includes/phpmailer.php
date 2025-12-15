<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;

add_action('phpmailer_init', function ($mail) {
  $cfg = qbnox_get_config();
  if (!$cfg) return;

  $mail->isSMTP();
  $mail->Host = $cfg['host'];
  $mail->Port = $cfg['port'];
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

  if ($cfg['auth'] === 'oauth') {
    $token = get_site_option('qbnox_oauth_token');
    $mail->SMTPAuth = true;
    $mail->AuthType = 'XOAUTH2';
    $mail->setOAuth(new OAuth([
      'provider' => $cfg['provider_obj'],
      'clientId' => $cfg['client_id'],
      'clientSecret' => qbnox_decrypt($cfg['client_secret']),
      'refreshToken' => qbnox_decrypt($token['refresh']),
      'userName' => $cfg['from_email'],
    ]));
  } elseif ($cfg['auth'] === 'password') {
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['username'];
    $mail->Password = $cfg['password'];
  } else {
    $mail->SMTPAuth = false;
  }
});
