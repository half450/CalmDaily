<?php

function start_admin_session()
{
    $cfg = app_config();
    $name = isset($cfg['session_name']) ? $cfg['session_name'] : 'calmdaily_admin';
    $cookiePath = '/';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $cookiePath = dirname($_SERVER['SCRIPT_NAME']);
        if (substr($cookiePath, -1) !== '/') {
            $cookiePath .= '/';
        }
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($name);
        session_set_cookie_params(0, $cookiePath, '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);
        session_start();
    }
}

function login_attempt_key()
{
    return 'login_attempts_' . md5(client_ip());
}

function is_login_locked()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $cfg = app_config();
    $max = isset($cfg['login_max_attempts']) ? (int) $cfg['login_max_attempts'] : 5;
    $lockout = isset($cfg['login_lockout_seconds']) ? (int) $cfg['login_lockout_seconds'] : 900;
    $key = login_attempt_key();

    if (empty($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        return false;
    }

    $attempts = $_SESSION[$key];
    if ($attempts['count'] < $max) {
        return false;
    }

    return (time() - $attempts['last']) < $lockout;
}

function register_failed_login()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $key = login_attempt_key();
    if (empty($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = array('count' => 0, 'last' => time());
    }

    $_SESSION[$key]['count'] += 1;
    $_SESSION[$key]['last'] = time();
}

function clear_failed_login()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    unset($_SESSION[login_attempt_key()]);
}

function attempt_login($username, $password)
{
    start_admin_session();

    if (is_login_locked()) {
        return '登录尝试过多，请稍后再试。';
    }

    $username = sanitize_string($username, 50);
    if ($username === '') {
        register_failed_login();
        return '用户名或密码错误。';
    }

    try {
        $user = find_admin_user($username);
    } catch (Exception $e) {
        return '数据库连接失败，请检查 db.php 与数据表是否已初始化。';
    }

    if (!$user || (int) $user['status'] !== 1 || !verify_password_hash($password, $user['password_hash'])) {
        register_failed_login();
        return '用户名或密码错误。';
    }

    session_regenerate_id(true);
    clear_failed_login();
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int) $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_login_at'] = time();

    return true;
}

function require_admin()
{
    start_admin_session();
    if (empty($_SESSION['admin_logged_in'])) {
        redirect('index.php');
    }
}

function logout_admin()
{
    start_admin_session();
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
