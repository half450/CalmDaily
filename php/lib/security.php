<?php

function secure_compare($known, $user)
{
    if (!is_string($known) || !is_string($user)) {
        return false;
    }
    $len = strlen($known);
    if ($len !== strlen($user)) {
        return false;
    }
    $result = 0;
    for ($i = 0; $i < $len; $i++) {
        $result |= ord($known[$i]) ^ ord($user[$i]);
    }
    return $result === 0;
}

function csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && secure_compare($_SESSION['csrf_token'], (string) $token);
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function request_method()
{
    return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
}

function is_post()
{
    return request_method() === 'POST';
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function json_response($data, $statusCode)
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code($statusCode);
    }
    echo json_encode($data);
    exit;
}

function sanitize_string($value, $maxLen)
{
    $value = trim((string) $value);
    if (strlen($value) > $maxLen) {
        $value = substr($value, 0, $maxLen);
    }
    return $value;
}

function sanitize_url($value, $maxLen)
{
    $value = sanitize_string($value, $maxLen);
    if ($value === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $value)) {
        return '';
    }
    return $value;
}

function sanitize_forward_type($value)
{
    $value = sanitize_string($value, 8);
    if ($value === '' || !preg_match('/^\d+$/', $value)) {
        return '';
    }
    return $value;
}

function sanitize_cont_id($value)
{
    $value = sanitize_string($value, 32);
    if ($value === '' || !preg_match('/^\d+$/', $value)) {
        return '';
    }
    return $value;
}

function allowed_forward_types()
{
    return array(
        '4'  => '稿件',
        '5'  => '视频',
        '6'  => '外链',
        '8'  => '直播',
        '9'  => '专题',
        '36' => '小程序',
        '54' => '圈子',
    );
}

function client_ip()
{
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return '0.0.0.0';
}
