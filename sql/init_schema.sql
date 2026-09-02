-- =============================================================================
-- CalmDaily 舒压日报 — 数据库初始化脚本（MySQL 5.7+）
-- 连接方式与 AITop10 相同：mysqli + php/db.php（生产用 weixin/db.php）
-- =============================================================================

/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `calm_daily`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `calm_daily`;

CREATE TABLE IF NOT EXISTS `calmdaily_admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '登录名',
  `password_hash` varchar(255) NOT NULL COMMENT 'bcrypt 密码哈希',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 启用，0 禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台用户';

CREATE TABLE IF NOT EXISTS `calmdaily_live` (
  `id` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `enabled` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 显示直播区块',
  `cont_id` varchar(32) NOT NULL DEFAULT '',
  `forward_type` varchar(8) NOT NULL DEFAULT '8',
  `stream_url` varchar(500) NOT NULL DEFAULT '',
  `title` varchar(120) NOT NULL DEFAULT '',
  `poster` varchar(500) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='直播配置';

CREATE TABLE IF NOT EXISTS `calmdaily_nav_button` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `img` varchar(200) NOT NULL DEFAULT '',
  `alt` varchar(40) NOT NULL DEFAULT '',
  `jump_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `forward_type` varchar(8) DEFAULT NULL,
  `cont_id` varchar(32) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导航按钮';

CREATE TABLE IF NOT EXISTS `calmdaily_waterfall` (
  `id` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `show_time` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 显示稿件时间',
  `cont_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '专题 ID，逗号分隔',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='瀑布流配置';

-- 默认管理员 admin / changeme
INSERT INTO `calmdaily_admin_user` (`username`, `password_hash`, `status`)
SELECT 'admin', '$2y$10$NNDwgT2R42Ub9jMuXUaPFue.VwBP8sBIRV8vEX2N8fk/1gzLCjxRu', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `calmdaily_admin_user` WHERE `username` = 'admin' LIMIT 1);

INSERT INTO `calmdaily_live` (`id`, `enabled`, `cont_id`, `forward_type`, `stream_url`, `title`, `poster`)
SELECT 1, 1, '1458876', '8', 'https://css.cyol.com/zqh/1458876.m3u8', '“青年舒压艺术空间”慢直播',
  'https://prod-cyolcos-1409912444.cos.ap-beijing.myqcloud.com/image/20260902/1788311233408.JPEG?imageMogr2/thumbnail/1000x/ignore-error/1'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `calmdaily_live` WHERE `id` = 1 LIMIT 1);

INSERT INTO `calmdaily_waterfall` (`id`, `show_time`, `cont_ids`)
SELECT 1, 0, '168540,265762,265746'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `calmdaily_waterfall` WHERE `id` = 1 LIMIT 1);

INSERT INTO `calmdaily_nav_button` (`sort_order`, `img`, `alt`, `jump_enabled`, `forward_type`, `cont_id`, `link_url`)
SELECT t.sort_order, t.img, t.alt, t.jump_enabled, t.forward_type, t.cont_id, t.link_url
FROM (
  SELECT 1 AS sort_order, 'assets/gaishanshimian.png' AS img, '改善睡眠' AS alt, 1 AS jump_enabled, '54' AS forward_type, '5' AS cont_id, NULL AS link_url
  UNION ALL SELECT 2, 'assets/yundongjianshen.png', '运动健身', 1, '9', '168540', NULL
  UNION ALL SELECT 3, 'assets/zhuanjiakecheng.png', '专家课程', 1, '6', NULL, 'https://news.youth.cn/zt/wrxlzzcz/'
  UNION ALL SELECT 4, 'assets/shuyazixun.png', '舒压咨询', 0, NULL, NULL, NULL
  UNION ALL SELECT 5, 'assets/yishengkepu.png', '医生科普', 0, NULL, NULL, NULL
  UNION ALL SELECT 6, 'assets/xueyezhichang.png', '学业职场', 0, NULL, NULL, NULL
  UNION ALL SELECT 7, 'assets/jiatingshejiao.png', '家庭社交', 0, NULL, NULL, NULL
  UNION ALL SELECT 8, 'assets/xinlijiankang.png', '心理健康', 0, NULL, NULL, NULL
  UNION ALL SELECT 9, 'assets/kexuejianzhong.png', '科学减重', 0, NULL, NULL, NULL
) AS t
WHERE NOT EXISTS (SELECT 1 FROM `calmdaily_nav_button` LIMIT 1);
