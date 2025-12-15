<?php
function qbnox_encrypt($data){
  $key = hash('sha256', wp_salt('secure_auth'));
  return openssl_encrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}

function qbnox_decrypt($data){
  $key = hash('sha256', wp_salt('secure_auth'));
  return openssl_decrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}
