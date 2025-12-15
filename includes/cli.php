<?php
if (defined('WP_CLI') && WP_CLI) {
  WP_CLI::add_command('qbnox smtp test', function () {
    wp_mail(get_option('admin_email'), 'Qbnox SMTP Test', 'This is a CLI test email.');
    WP_CLI::success('Test email sent');
  });
}
