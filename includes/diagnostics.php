<?php
add_action('network_admin_menu', function(){
  add_submenu_page('qbnox-smtp-logs','Diagnostics','Diagnostics','manage_network_options','qbnox-smtp-diag','qbnox_diag_page');
});

function qbnox_diag_page(){
  echo '<div class="wrap"><h1>Diagnostics</h1>';
  echo '<p>PHP Version: '.PHP_VERSION.'</p>';
  echo '<p>WordPress Version: '.get_bloginfo('version').'</p>';
  echo '<p>Multisite: '.(is_multisite()?'Yes':'No').'</p>';
  echo '</div>';
}
