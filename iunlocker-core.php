<?php

class iUnlocker {
  
  private $settings;
  private $session_id;

  public function __construct() {
    global $pagenow;

    // plugins settings
    $this->settings = get_option('iunlocker_setting');

    $action = $this->get('action');
    $key = $this->get('key');

    // event binding
    add_action('admin_menu', array($this, 'iunlocker_menu'));
    add_action('wp_ajax_nopriv_iunlocker_is_login', array($this, 'is_login'));
    add_action('wp_ajax_nopriv_iunlocker_login', array($this, 'login'));
    add_action('wp_ajax_nopriv_iunlocker_oauth', array($this, 'oauth'));
    
    if ($pagenow == 'wp-login.php' && $action != 'logout' && !$key) {
      if (is_user_logged_in()) {
        $referer = wp_get_referer();
        $referer = $referer ? $referer : admin_url();
        wp_safe_redirect($referer);
      } else {
        add_action('init', array($this, 'login_page'), 98);
      }
    }
  }

  public function iunlocker_menu() {
    add_options_page('iUnlocker', 'iUnlocker', 'manage_options', basename(__FILE__), array($this, 'iunlocker_setting_page'));
  }

  public function iunlocker_setting_page(){
    @include 'include/iunlocker-setting.php';
  }

  public function login_page() {
    @require_once('include/iunlocker-login.php');
    exit();
  }

  public function setting($key) {
    return $this->settings[$key];
  }

  public function source($file_name) {
    return sprintf('%s/assets/%s', IUNLOCKER_URL, $file_name);
  }

  private function set_session() {
    $request_time = $_SERVER['REQUEST_TIME_FLOAT'] / rand(9, 99);
    $user_cookies = $_SERVER['HTTP_COOKIE'];
    $request_ip = $_SERVER['REMOTE_ADDR'];

    // encrypt thoese infomation
    $hash = wp_hash($request_ip . $user_cookies . $request_time);

    // ensure session id is the only one
    $session_id = substr($hash, -12, 10);

    // sync session id
    $this->session_id = $session_id;

    // session will be destoried 
    // when the bowser is closed
    $_SESSION['iunlocker'] = $session_id;
  }

  private function set_token() {
    $session_id = $this->session_id;

    $token_key = $session_id . '_token';
    $scaned_key = $session_id . '_scaned';

    // encrypt session id
    $token = md5($session_id . $this->setting('UUID'));

    // let token cache for 60 seconds
    $this->set_cache($token_key, $token);

    // delete the scaned cache
    $this->del_cache($scaned_key);
  }

  public function session() {
    $this->set_session();
    $this->set_token();

    echo $this->session_id;
  }

  public function appkey() {
    $request_time = $_SERVER['REQUEST_TIME_FLOAT'] / rand(9, 99);

    // encrypt thoese infomation
    $appkey = wp_hash($request_ip . $user_cookies . $request_time);

    $this->set_cache('appkey', $appkey, 600);

    echo $appkey;
  }

  public function is_login() {
    $session_id = $this->post('session');
    
    if (!$session_id) {
      $this->error_response(400, array(
        'error' => 'Invalid session'
      ));
    }

    if ($_SESSION['iunlocker'] != $session_id) {
      $this->error_response(401, array(
        'error' => 'Invalid session'
      ));
    } else {
      $logined_key = $session_id . '_logined';
      $token_key = $session_id . '_token';
      $scaned_key = $session_id . '_scaned';

      if ($this->get_cache($logined_key)) {
        unset($_SESSION['iunlocker']);

        $this->del_cache($logined_key);
        $this->del_cache($token_key);
        $this->del_cache($scaned_key);

        $user_id = 1;
        $user = get_user_by('ID', $user_id);
        $user_login = $user->data->user_login;

        $sessions = WP_Session_Tokens::get_instance($user_id);
        $sessions->destroy_others( wp_get_session_token() );
        
        wp_set_current_user($user_id, $user_login);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $user_login);

        $this->success_response(array(
          'code' => 200
        ));
      } else {
        if (!$this->get_cache($token_key)) {
          $this->error_response(401, array(
            'error' => 'Session expired'
          ));
        } else {
          if ($this->get_cache($scaned_key)) {
            $this->success_response(array(
              'code' => 300
            ));
          }
        }
      }
    }

    $this->success_response(array(
      'code' => 100
    ));
  }

  public function login() {
    $session_id = $this->post('appkey');

    if (!$session_id) {
      $this->error_response(400, array(
        'error' => 'Invalid session'
      ));
    }

    $token_key = $session_id . '_token';
    $cache_token = $this->get_cache($token_key);

    if (!$cache_token) {
      $this->error_response(401, array(
        'error' => 'Session expired'
      ));
    }

    if ($this->post('scaned')) {
      $scaned_key = $session_id . '_scaned';
      $this->set_cache($scaned_key, true);

      $this->success_response(array(
        'message' => 'success scaned'
      ));
    } else {
      $token = $this->post('token');

      $scaned_key = $session_id . '_logined';

      if (!$token) {
        $this->error_response(400, array(
          'error' => 'Invalid token'
        ));
      } else {
        if ($cache_token == $token) {
          $logined_key = $session_id . '_logined';
          $this->set_cache($logined_key, true);

          $this->success_response(array(
            'message' => '登录成功'
          ));
        } else {
          $this->error_response(500, array(
            'error' => 'Invalid request'
          ));
        }
      }
    }
  }

  public function oauth() {
    if (!$this->post('appkey')) {
      $this->error_response(400, array(
        'error' => 'Invalid appkey'
      ));
    }

    if (!$this->settings) {
      $this->settings = array();
    }

    $this->settings = array(
      'UUID' => $this->post('UUID'),
      'device_name' => $this->post('deviceName'),
      'system_version' => $this->post('systemVersion'),
      'system_info' => $this->post('systemInfo')
    );

    $this->del_cache('appkey');
    update_option('iunlocker_setting', $this->settings);

    $this->success_response(array(
      'message' => '绑定网站成功'
    ));
  }

  private function set_cache($key, $value, $expire = 60) {
    if ($this->has_memory_cache()) {
      wp_cache_set($key, $value, 'iunlocker', $expire);
    } else {
      set_transient('iunlocker_' . $key, $value, $expire);
    }
  }

  private function get_cache($key) {
    if ($this->has_memory_cache()) {
      $cache = wp_cache_get($key, 'iunlocker');
    } else {
      $cache = get_transient('iunlocker_' . $key);
    }

    return $cache;
  }

  private function del_cache($key) {
    if ($this->has_memory_cache()) {
      wp_cache_delete($key, 'iunlocker');
    } else {
      delete_transient('iunlocker_' . $key);
    }
  }

  private function post($key) {
    $key = $_POST[ $key ];

    return $key;
  }

  private function get($key) {
    if (!isset($_GET[$key])) return false;

    $key = esc_attr(esc_html($_GET[ $key ]));

    return $key;
  }

  private function has_memory_cache () {
    return false;
  }

  private function error_response($code, $result) {
    switch ($code) {
      case 301:
        header('HTTP/1.1 301 Moved Permanently');
        break;

      case 400:
        header('HTTP/1.1 400 INVALID REQUEST');
        break;

      case 401:
        header('HTTP/1.1 401 Unauthorized');
        break;

      case 500:
        header('HTTP/1.0 500 INTERNAL SERVER ERROR');
        break;

      default:
        header('HTTP/1.1 404 NOT FOUND');
    }

    header('Content-Type: application/json;charset=UTF-8');
    exit(json_encode($result));
  }

  private function success_response($result) {
    header('HTTP/1.1 200 OK');
    header('Content-type: application/json;charset=UTF-8');
    exit(json_encode($result));
  }    
}