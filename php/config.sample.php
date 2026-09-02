<?php
/**
 * 复制本文件为 config.local.php 并按需调整。
 * 数据库账号写在 php/db.php（测试）或服务器 weixin/db.php（生产），不在此文件配置。
 */

return array(
    'timezone' => 'Asia/Shanghai',
    'db_time_zone' => '+08:00',
    'session_name' => 'calmdaily_admin',
    'login_max_attempts' => 5,
    'login_lockout_seconds' => 900,
);
