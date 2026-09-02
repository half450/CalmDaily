<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

require_admin();

$message = '';
$error = '';
$dbError = get_db_last_error();
$config = read_site_config();
$forwardTypes = allowed_forward_types();

if (is_post()) {
    if (!verify_csrf(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = '请求无效，请刷新页面后重试。';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $ok = false;

        if ($action === 'live') {
            $ok = save_live_from_post($_POST);
            $message = $ok ? '直播配置已保存。' : '直播配置保存失败。';
        } elseif ($action === 'nav') {
            $ok = save_nav_from_post($_POST, $_FILES);
            $message = $ok ? '导航按钮已保存。' : '导航按钮保存失败，请检查图标与名称。';
        } elseif ($action === 'waterfall') {
            $ok = save_waterfall_from_post($_POST);
            $message = $ok ? '瀑布流配置已保存。' : '瀑布流配置保存失败。';
        } else {
            $error = '未知操作。';
        }

        if (!$ok && $error === '') {
            $error = $message;
            $message = '';
        }

        $dbError = get_db_last_error();
        $config = read_site_config();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CalmDaily 后台管理</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <header class="topbar">
    <div>
      <strong>CalmDaily 后台</strong>
      <span class="muted">管理直播、导航按钮与瀑布流</span>
    </div>
    <a class="link-btn" href="logout.php">退出</a>
  </header>

  <main class="container">
    <?php if ($message !== ''): ?><p class="success"><?php echo e($message); ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
    <?php if ($dbError !== ''): ?><p class="error">数据库：<?php echo e($dbError); ?></p><?php endif; ?>

    <section class="panel">
      <h2>直播配置</h2>
      <form method="post" action="dashboard.php">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <input type="hidden" name="action" value="live" />
        <label class="check-row">
          <input type="checkbox" name="live_enabled" value="1" <?php echo !empty($config['live']['enabled']) ? 'checked' : ''; ?> />
          显示直播区块
        </label>
        <div class="grid-2">
          <label>直播稿件 ID (contId)
            <input type="text" name="live_cont_id" value="<?php echo e($config['live']['contId']); ?>" maxlength="32" required />
          </label>
          <label>跳转类型 (forwardType)
            <input type="text" name="live_forward_type" value="<?php echo e($config['live']['forwardType']); ?>" maxlength="8" required />
          </label>
        </div>
        <label>直播流地址 (m3u8)
          <input type="url" name="live_stream_url" value="<?php echo e($config['live']['streamUrl']); ?>" maxlength="500" required />
        </label>
        <label>直播标题
          <input type="text" name="live_title" value="<?php echo e($config['live']['title']); ?>" maxlength="120" required />
        </label>
        <label>封面图 URL
          <input type="url" name="live_poster" value="<?php echo e($config['live']['poster']); ?>" maxlength="500" />
        </label>
        <button type="submit">保存直播配置</button>
      </form>
    </section>

    <section class="panel">
      <h2>导航按钮</h2>
      <p class="hint">forwardType 常用：4 稿件、6 外链、8 直播、9 专题、36 小程序、54 圈子。未勾选「启用跳转」时按钮不可点击。</p>
      <form method="post" action="dashboard.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <input type="hidden" name="action" value="nav" />
        <div class="nav-table">
          <?php foreach ($config['navButtons'] as $i => $btn): ?>
            <?php
              $jump = isset($btn['jump']) && is_array($btn['jump']) ? $btn['jump'] : null;
              $ft = $jump ? $jump['forwardType'] : '9';
              $contId = ($jump && isset($jump['contId'])) ? $jump['contId'] : '';
              $link = ($jump && isset($jump['link'])) ? $jump['link'] : '';
            ?>
            <div class="nav-row">
              <div class="nav-preview">
                <img src="../../html/<?php echo e($btn['img']); ?>" alt="" />
              </div>
              <div class="nav-fields">
                <input type="hidden" name="nav_img_existing[<?php echo $i; ?>]" value="<?php echo e($btn['img']); ?>" />
                <label>按钮名称
                  <input type="text" name="nav_alt[<?php echo $i; ?>]" value="<?php echo e($btn['alt']); ?>" maxlength="40" required />
                </label>
                <label>更换图标（PNG/JPG/GIF，≤500KB）
                  <input type="file" name="nav_img[<?php echo $i; ?>]" accept="image/png,image/jpeg,image/gif" />
                </label>
                <label class="check-row">
                  <input type="checkbox" name="nav_enabled[<?php echo $i; ?>]" value="1" <?php echo $jump ? 'checked' : ''; ?> />
                  启用跳转
                </label>
                <div class="grid-2">
                  <label>forwardType
                    <select name="nav_forward_type[<?php echo $i; ?>]">
                      <?php foreach ($forwardTypes as $value => $label): ?>
                        <option value="<?php echo e($value); ?>" <?php echo $ft === $value ? 'selected' : ''; ?>><?php echo e($value . ' · ' . $label); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>contId
                    <input type="text" name="nav_cont_id[<?php echo $i; ?>]" value="<?php echo e($contId); ?>" maxlength="32" />
                  </label>
                </div>
                <label>外链 URL（forwardType=6 时使用）
                  <input type="url" name="nav_link[<?php echo $i; ?>]" value="<?php echo e($link); ?>" maxlength="500" />
                </label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="submit">保存导航按钮</button>
      </form>
    </section>

    <section class="panel">
      <h2>瀑布流</h2>
      <form method="post" action="dashboard.php">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <input type="hidden" name="action" value="waterfall" />
        <label class="check-row">
          <input type="checkbox" name="waterfall_show_time" value="1" <?php echo !empty($config['waterfall']['showTime']) ? 'checked' : ''; ?> />
          显示稿件发布时间
        </label>
        <label>专题 ID 列表（逗号或换行分隔）
          <textarea name="waterfall_cont_ids" rows="3"><?php echo e(implode(', ', $config['waterfall']['contIds'])); ?></textarea>
        </label>
        <button type="submit">保存瀑布流配置</button>
      </form>
    </section>

    <section class="panel muted-panel">
      <h2>前端接口</h2>
      <p>公开配置 JSON：<code>/php/api/config.php</code>（数据来自 MySQL）</p>
      <p>前端页面：<code>/html/CalmDaily.html</code></p>
      <p>数据库初始化：<code>sql/init_schema.sql</code></p>
    </section>
  </main>
</body>
</html>
