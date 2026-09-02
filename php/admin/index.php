<?php
require_once dirname(__FILE__) . '/../lib/bootstrap.php';

start_admin_session();

$error = '';
if (is_post()) {
    if (!verify_csrf(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = '请求无效，请刷新页面后重试。';
    } elseif (is_login_locked()) {
        $error = '登录尝试过多，请稍后再试。';
    } else {
        $result = attempt_login(
            isset($_POST['username']) ? $_POST['username'] : '',
            isset($_POST['password']) ? $_POST['password'] : ''
        );
        if ($result === true) {
            redirect('dashboard.php');
        }
        $error = $result;
    }
}

if (!empty($_SESSION['admin_logged_in'])) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CalmDaily 后台登录</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <main class="auth-card">
    <h1>CalmDaily 后台</h1>
    <p class="hint">数据库连接写在 <code>php/db.php</code>（生产环境用 <code>weixin/db.php</code>）。首次部署请执行 <code>sql/init_schema.sql</code>，默认账号 admin / changeme。</p>
    <?php if ($error !== ''): ?>
      <p class="error"><?php echo e($error); ?></p>
    <?php endif; ?>
    <form method="post" action="index.php" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
      <label>
        用户名
        <input type="text" name="username" required maxlength="32" />
      </label>
      <label>
        密码
        <input type="password" name="password" required maxlength="128" />
      </label>
      <button type="submit">登录</button>
    </form>
  </main>
</body>
</html>
