<?php

function db_conn()
{
    return Db::get(app_config());
}

function default_site_config()
{
    return array(
        'live' => array(
            'enabled' => true,
            'contId' => '1458876',
            'forwardType' => '8',
            'streamUrl' => 'https://css.cyol.com/zqh/1458876.m3u8',
            'title' => '“青年舒压艺术空间”慢直播',
            'poster' => 'https://prod-cyolcos-1409912444.cos.ap-beijing.myqcloud.com/image/20260902/1788311233408.JPEG?imageMogr2/thumbnail/1000x/ignore-error/1',
        ),
        'navButtons' => array(
            array('img' => 'assets/gaishanshimian.png', 'alt' => '改善睡眠', 'jump' => array('forwardType' => '54', 'contId' => '5')),
            array('img' => 'assets/yundongjianshen.png', 'alt' => '运动健身', 'jump' => array('forwardType' => '9', 'contId' => '168540')),
            array('img' => 'assets/zhuanjiakecheng.png', 'alt' => '专家课程', 'jump' => array('forwardType' => '6', 'link' => 'https://news.youth.cn/zt/wrxlzzcz/')),
            array('img' => 'assets/shuyazixun.png', 'alt' => '舒压咨询', 'jump' => null),
            array('img' => 'assets/yishengkepu.png', 'alt' => '医生科普', 'jump' => null),
            array('img' => 'assets/xueyezhichang.png', 'alt' => '学业职场', 'jump' => null),
            array('img' => 'assets/jiatingshejiao.png', 'alt' => '家庭社交', 'jump' => null),
            array('img' => 'assets/xinlijiankang.png', 'alt' => '心理健康', 'jump' => null),
            array('img' => 'assets/kexuejianzhong.png', 'alt' => '科学减重', 'jump' => null),
        ),
        'waterfall' => array(
            'showTime' => false,
            'contIds' => array('168540', '265762', '265746'),
        ),
    );
}

function read_site_config()
{
    try {
        $db = db_conn();
        $liveRow = $db->prepare('SELECT enabled, cont_id, forward_type, stream_url, title, poster FROM calmdaily_live WHERE id = 1 LIMIT 1');
        $liveRow->execute(array());
        $live = $liveRow->fetch();

        $navStmt = $db->prepare('SELECT img, alt, jump_enabled, forward_type, cont_id, link_url FROM calmdaily_nav_button ORDER BY sort_order ASC, id ASC');
        $navStmt->execute(array());
        $navRows = $navStmt->fetchAll();

        $wfRow = $db->prepare('SELECT show_time, cont_ids FROM calmdaily_waterfall WHERE id = 1 LIMIT 1');
        $wfRow->execute(array());
        $wf = $wfRow->fetch();

        if (!$live || empty($navRows) || !$wf) {
            return default_site_config();
        }

        return normalize_site_config(array(
            'live' => array(
                'enabled' => (int) $live['enabled'] === 1,
                'contId' => $live['cont_id'],
                'forwardType' => $live['forward_type'],
                'streamUrl' => $live['stream_url'],
                'title' => $live['title'],
                'poster' => $live['poster'],
            ),
            'navButtons' => rows_to_nav_buttons($navRows),
            'waterfall' => array(
                'showTime' => (int) $wf['show_time'] === 1,
                'contIds' => parse_cont_ids($wf['cont_ids']),
            ),
        ));
    } catch (Exception $e) {
        return default_site_config();
    }
}

function set_db_last_error($message)
{
    $GLOBALS['calmdaily_db_error'] = $message;
}

function get_db_last_error()
{
    return isset($GLOBALS['calmdaily_db_error']) ? $GLOBALS['calmdaily_db_error'] : '';
}

function rows_to_nav_buttons($rows)
{
    $buttons = array();
    foreach ($rows as $row) {
        $jump = null;
        if ((int) $row['jump_enabled'] === 1) {
            $forwardType = isset($row['forward_type']) ? (string) $row['forward_type'] : '';
            if ($forwardType === '6' && !empty($row['link_url'])) {
                $jump = array('forwardType' => '6', 'link' => $row['link_url']);
            } elseif ($forwardType !== '' && !empty($row['cont_id'])) {
                $jump = array('forwardType' => $forwardType, 'contId' => (string) $row['cont_id']);
            }
        }

        $buttons[] = array(
            'img' => $row['img'],
            'alt' => $row['alt'],
            'jump' => $jump,
        );
    }

    return $buttons;
}

function parse_cont_ids($raw)
{
    $ids = array();
    foreach (preg_split('/[\s,，]+/', trim((string) $raw)) as $id) {
        $clean = sanitize_cont_id($id);
        if ($clean !== '') {
            $ids[] = $clean;
        }
    }
    return $ids;
}

function normalize_site_config($config)
{
    $defaults = default_site_config();
    $out = $defaults;

    if (isset($config['live']) && is_array($config['live'])) {
        $live = $config['live'];
        $out['live']['enabled'] = !empty($live['enabled']);
        $out['live']['contId'] = sanitize_cont_id(isset($live['contId']) ? $live['contId'] : $defaults['live']['contId']);
        $out['live']['forwardType'] = sanitize_forward_type(isset($live['forwardType']) ? $live['forwardType'] : $defaults['live']['forwardType']);
        $out['live']['streamUrl'] = sanitize_url(isset($live['streamUrl']) ? $live['streamUrl'] : $defaults['live']['streamUrl'], 500);
        $out['live']['title'] = sanitize_string(isset($live['title']) ? $live['title'] : $defaults['live']['title'], 120);
        $out['live']['poster'] = sanitize_url(isset($live['poster']) ? $live['poster'] : $defaults['live']['poster'], 500);
    }

    if (isset($config['navButtons']) && is_array($config['navButtons'])) {
        $buttons = array();
        foreach ($config['navButtons'] as $btn) {
            if (!is_array($btn)) {
                continue;
            }
            $normalizedBtn = normalize_nav_button($btn);
            if ($normalizedBtn !== null) {
                $buttons[] = $normalizedBtn;
            }
        }
        if (!empty($buttons)) {
            $out['navButtons'] = $buttons;
        }
    }

    if (isset($config['waterfall']) && is_array($config['waterfall'])) {
        $wf = $config['waterfall'];
        $out['waterfall']['showTime'] = !empty($wf['showTime']);
        if (isset($wf['contIds']) && is_array($wf['contIds'])) {
            $ids = array();
            foreach ($wf['contIds'] as $id) {
                $clean = sanitize_cont_id($id);
                if ($clean !== '') {
                    $ids[] = $clean;
                }
            }
            if (!empty($ids)) {
                $out['waterfall']['contIds'] = $ids;
            }
        }
    }

    return $out;
}

function normalize_nav_button($btn)
{
    $img = sanitize_nav_image_path(isset($btn['img']) ? $btn['img'] : '');
    $alt = sanitize_string(isset($btn['alt']) ? $btn['alt'] : '', 40);
    if ($img === '' || $alt === '') {
        return null;
    }

    $jump = null;
    if (isset($btn['jump']) && is_array($btn['jump'])) {
        $jump = normalize_jump($btn['jump']);
    }

    return array(
        'img' => $img,
        'alt' => $alt,
        'jump' => $jump,
    );
}

function normalize_jump($jump)
{
    $forwardType = sanitize_forward_type(isset($jump['forwardType']) ? $jump['forwardType'] : '');
    if ($forwardType === '') {
        return null;
    }

    if ($forwardType === '6') {
        $link = sanitize_url(isset($jump['link']) ? $jump['link'] : '', 500);
        if ($link === '') {
            return null;
        }
        return array('forwardType' => '6', 'link' => $link);
    }

    $contId = sanitize_cont_id(isset($jump['contId']) ? $jump['contId'] : '');
    if ($contId === '') {
        return null;
    }

    return array('forwardType' => $forwardType, 'contId' => $contId);
}

function sanitize_nav_image_path($path)
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^assets/nav-uploads/[a-zA-Z0-9._-]+$#', $path)) {
        return $path;
    }

    if (preg_match('#^assets/[a-zA-Z0-9._-]+\.(png|jpe?g|gif|webp)$#i', $path)) {
        return $path;
    }

    return '';
}

function public_site_config()
{
    return read_site_config();
}

function save_live_from_post($post)
{
    try {
        $config = normalize_site_config(array(
            'live' => array(
                'enabled' => !empty($post['live_enabled']),
                'contId' => isset($post['live_cont_id']) ? $post['live_cont_id'] : '',
                'forwardType' => isset($post['live_forward_type']) ? $post['live_forward_type'] : '8',
                'streamUrl' => isset($post['live_stream_url']) ? $post['live_stream_url'] : '',
                'title' => isset($post['live_title']) ? $post['live_title'] : '',
                'poster' => isset($post['live_poster']) ? $post['live_poster'] : '',
            ),
        ));

        $live = $config['live'];
        $db = db_conn();
        $stmt = $db->prepare(
            'UPDATE calmdaily_live SET enabled = ?, cont_id = ?, forward_type = ?, stream_url = ?, title = ?, poster = ? WHERE id = 1'
        );
        $stmt->execute(array(
            $live['enabled'] ? 1 : 0,
            $live['contId'],
            $live['forwardType'],
            $live['streamUrl'],
            $live['title'],
            $live['poster'],
        ));

        set_db_last_error('');
        return true;
    } catch (Exception $e) {
        set_db_last_error($e->getMessage());
        return false;
    }
}

function save_waterfall_from_post($post)
{
    try {
        $rawIds = isset($post['waterfall_cont_ids']) ? $post['waterfall_cont_ids'] : '';
        $ids = array();
        foreach (preg_split('/[\s,，]+/', trim($rawIds)) as $id) {
            $clean = sanitize_cont_id($id);
            if ($clean !== '') {
                $ids[] = $clean;
            }
        }

        if (empty($ids)) {
            return false;
        }

        $db = db_conn();
        $stmt = $db->prepare('UPDATE calmdaily_waterfall SET show_time = ?, cont_ids = ? WHERE id = 1');
        $stmt->execute(array(
            !empty($post['waterfall_show_time']) ? 1 : 0,
            implode(',', $ids),
        ));

        set_db_last_error('');
        return true;
    } catch (Exception $e) {
        set_db_last_error($e->getMessage());
        return false;
    }
}

function save_nav_from_post($post, $files)
{
    try {
        $buttons = array();
        $rows = isset($post['nav_alt']) && is_array($post['nav_alt']) ? $post['nav_alt'] : array();

        foreach ($rows as $index => $alt) {
            $alt = sanitize_string($alt, 40);
            if ($alt === '') {
                continue;
            }

            $existingImg = sanitize_nav_image_path(isset($post['nav_img_existing'][$index]) ? $post['nav_img_existing'][$index] : '');
            $img = $existingImg;

            if (isset($files['nav_img']['name'][$index]) && $files['nav_img']['error'][$index] === UPLOAD_ERR_OK) {
                $uploaded = save_nav_upload(
                    $files['nav_img']['tmp_name'][$index],
                    $files['nav_img']['name'][$index],
                    $files['nav_img']['size'][$index]
                );
                if ($uploaded !== '') {
                    $img = $uploaded;
                }
            }

            if ($img === '') {
                continue;
            }

            $enabled = !empty($post['nav_enabled'][$index]);
            $jump = null;
            if ($enabled) {
                $forwardType = sanitize_forward_type(isset($post['nav_forward_type'][$index]) ? $post['nav_forward_type'][$index] : '');
                if ($forwardType === '6') {
                    $link = sanitize_url(isset($post['nav_link'][$index]) ? $post['nav_link'][$index] : '', 500);
                    if ($link !== '') {
                        $jump = array('forwardType' => '6', 'link' => $link);
                    }
                } elseif ($forwardType !== '') {
                    $contId = sanitize_cont_id(isset($post['nav_cont_id'][$index]) ? $post['nav_cont_id'][$index] : '');
                    if ($contId !== '') {
                        $jump = array('forwardType' => $forwardType, 'contId' => $contId);
                    }
                }
            }

            $buttons[] = array(
                'img' => $img,
                'alt' => $alt,
                'jump' => $jump,
            );
        }

        if (empty($buttons)) {
            return false;
        }

        $buttons = normalize_site_config(array('navButtons' => $buttons));
        $buttons = $buttons['navButtons'];

        $db = db_conn();
        $db->beginTransaction();
        $db->exec('DELETE FROM calmdaily_nav_button');

        $insert = $db->prepare(
            'INSERT INTO calmdaily_nav_button (sort_order, img, alt, jump_enabled, forward_type, cont_id, link_url) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($buttons as $sort => $btn) {
            $jump = isset($btn['jump']) && is_array($btn['jump']) ? $btn['jump'] : null;
            $forwardType = $jump ? $jump['forwardType'] : null;
            $contId = ($jump && isset($jump['contId'])) ? $jump['contId'] : null;
            $link = ($jump && isset($jump['link'])) ? $jump['link'] : null;

            $insert->execute(array(
                $sort + 1,
                $btn['img'],
                $btn['alt'],
                $jump ? 1 : 0,
                $forwardType,
                $contId,
                $link,
            ));
        }

        $db->commit();
        set_db_last_error('');
        return true;
    } catch (Exception $e) {
        try {
            $db = db_conn();
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        } catch (Exception $ignored) {
        }
        set_db_last_error($e->getMessage());
        return false;
    }
}

function save_nav_upload($tmpPath, $originalName, $size)
{
    if ($size <= 0 || $size > 512000) {
        return '';
    }

    $info = @getimagesize($tmpPath);
    if ($info === false) {
        return '';
    }

    $allowed = array(
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_GIF => 'gif',
    );

    if (!isset($allowed[$info[2]])) {
        return '';
    }

    $ext = $allowed[$info[2]];
    $name = 'nav_' . date('YmdHis') . '_' . bin2hex(openssl_random_pseudo_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0750, true);
    }

    if (!move_uploaded_file($tmpPath, $dest)) {
        return '';
    }

    @chmod($dest, 0644);
    return 'assets/nav-uploads/' . $name;
}

function find_admin_user($username)
{
    $db = db_conn();
    $stmt = $db->prepare('SELECT id, username, password_hash, status FROM calmdaily_admin_user WHERE username = ? LIMIT 1');
    $stmt->execute(array($username));
    return $stmt->fetch();
}

function verify_password_hash($password, $hash)
{
    if (function_exists('password_verify')) {
        return password_verify($password, $hash);
    }

    return crypt((string) $password, (string) $hash) === (string) $hash;
}
