<?php

date_default_timezone_set('Asia/Shanghai');

define('PHP_ROOT', dirname(dirname(__FILE__)));
define('UPLOAD_DIR', PHP_ROOT . '/../html/assets/nav-uploads');

require_once dirname(__FILE__) . '/security.php';
require_once dirname(__FILE__) . '/Db.php';
require_once dirname(__FILE__) . '/config_store.php';
require_once dirname(__FILE__) . '/auth.php';

function app_config()
{
    static $cfg = null;
    if ($cfg === null) {
        $defaults = array(
            'timezone' => 'Asia/Shanghai',
            'db_time_zone' => '+08:00',
            'session_name' => 'calmdaily_admin',
            'login_max_attempts' => 5,
            'login_lockout_seconds' => 900,
        );
        $local = PHP_ROOT . '/config.local.php';
        $sample = PHP_ROOT . '/config.sample.php';
        if (is_file($local)) {
            $fileCfg = require $local;
            if (is_array($fileCfg)) {
                $defaults = array_merge($defaults, $fileCfg);
            }
        } elseif (is_file($sample)) {
            $fileCfg = require $sample;
            if (is_array($fileCfg)) {
                $defaults = array_merge($defaults, $fileCfg);
            }
        }
        $cfg = $defaults;
    }
    return $cfg;
}

function bootstrap_database()
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $prodDbFile = PHP_ROOT . '/../../weixin/db.php';
    $localDbFile = PHP_ROOT . '/db.php';
    if (file_exists($prodDbFile)) {
        require_once $prodDbFile;
    } elseif (file_exists($localDbFile)) {
        require_once $localDbFile;
    } else {
        throw new Exception('db bootstrap file missing');
    }

    $loaded = true;
}

function ensure_runtime_dirs()
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
    }
}

bootstrap_database();
ensure_runtime_dirs();

if (isset(app_config()['timezone']) && app_config()['timezone'] !== '') {
    date_default_timezone_set(app_config()['timezone']);
}
