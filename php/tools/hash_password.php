#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

$password = isset($argv[1]) ? $argv[1] : '';
if ($password === '') {
    fwrite(STDERR, "用法: php hash_password.php 你的密码\n");
    exit(1);
}

$salt = '$2y$10$' . substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(16))), 0, 22);
echo crypt($password, $salt) . "\n";
