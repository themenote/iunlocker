<?php
/*
Plugin Name: iUnlocker
Plugin URI: http://www.puckjs.com/iunlocker
Description: 二维码登录 Wordpress Login With QRCode
Version: 1.0.0
Author: mufeng <mufeng.me@gmail.com>
Author URI: http://www.puckjs.com
*/

define('IUNLOCKER_URL', plugins_url('', __FILE__));
define('IUNLOCKER_PATH', dirname(__FILE__));
define('IUNLOCKER_ADMIN_URL', admin_url());

// activate the plugin and redirect to setting page
register_activation_hook(__FILE__, 'iunlocker_activate');
function iunlocker_activate() {
  add_option('iunlocker_redirect', true);

  if (!get_option('iunlocker_setting')) {
    update_option('iunlocker_setting', $settings);
  }
}

add_action('admin_init', 'iunlocker_redirect');
function iunlocker_redirect() {
  if (get_option('iunlocker_redirect', false)) {
    delete_option('iunlocker_redirect');
    wp_redirect('options-general.php?page=iunlocker-core.php');
  }
}

// initinal session
if (!isset($_SESSION)) {
  session_start();
}

// load core file
require IUNLOCKER_PATH . '/iunlocker-core.php';

add_action('plugins_loaded', 'iunlocker_core_initinal');
function iunlocker_core_initinal () {
  $iunlocker = new iUnlocker();  
}