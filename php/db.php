<?php
/**
 * 测试机数据库连接（部署到服务器 api/db.php 同级目录）。
 * 生产环境使用独立 weixin/db.php，由 bootstrap.php 按路径加载，勿与本文件混用。
 */
$mysqli = new mysqli();
$mysqli->connect('127.0.0.1', 'root', '1234567', 'calm_daily', 3306);
$mysqli->set_charset('utf8mb4');
