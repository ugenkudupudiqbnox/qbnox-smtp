<?php
function qbnox_get_config() {
  if (is_multisite() && get_option('qbnox_site_override')) {
    return get_option('qbnox_smtp_site');
  }
  return get_site_option('qbnox_smtp_network');
}
