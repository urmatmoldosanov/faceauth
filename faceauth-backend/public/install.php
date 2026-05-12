<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';

$store = faceauth_user_store();
$installed = $store->isInstalled();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $superadminUser = trim((string)($_POST['superadmin_user'] ?? ''));
    $superadminPassword = (string)($_POST['superadmin_password'] ?? '');
    $universityAdminUser = trim((string)($_POST['admin_user'] ?? ''));
    $universityAdminPassword = (string)($_POST['admin_password'] ?? '');

    try {
        $store->createInitialUsers($superadminUser, $superadminPassword, $universityAdminUser, $universityAdminPassword);
        $success = true;
        $installed = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FaceAuth Backend Installer</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.5; margin: 32px; max-width: 760px; }
    .card { border: 1px solid #ddd; border-radius: 8px; margin: 16px 0; padding: 16px; }
    label { display: flex; flex-direction: column; margin: 10px 0; }
    input { font-size: 16px; padding: 8px; }
    button { font-size: 16px; padding: 10px 14px; }
    .error { background: #ffecec; border-color: #e5a0a0; }
    .success { background: #ecfff1; border-color: #9ed3aa; }
    code { background: #f5f5f5; padding: 2px 5px; border-radius: 4px; }
  </style>
</head>
<body>
  <h1>Установка FaceAuth Backend</h1>

  <?php if ($success): ?>
    <div class="card success">
      <strong>Готово.</strong> Пользователи созданы. Перейдите в <a href="/admin/index.php">панель управления</a> и войдите под superadmin.
    </div>
  <?php elseif ($installed): ?>
    <div class="card success">
      Backend уже установлен. Перейдите в <a href="/admin/index.php">панель управления</a>.
    </div>
  <?php else: ?>
    <?php if ($error !== ''): ?>
      <div class="card error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card">
      <p>
        Этот мастер создаёт локальных пользователей панели: <code>superadmin</code> для управления backend
        и <code>admin</code> для университетской системы. Пароли сохраняются только как password hash.
      </p>
      <form method="post">
        <h2>Superadmin</h2>
        <label>
          Логин superadmin
          <input name="superadmin_user" required autocomplete="username" value="superadmin">
        </label>
        <label>
          Пароль superadmin
          <input name="superadmin_password" type="password" required minlength="8" autocomplete="new-password">
        </label>

        <h2>Админ университетской системы</h2>
        <label>
          Логин админа университета
          <input name="admin_user" required autocomplete="username" value="university_admin">
        </label>
        <label>
          Пароль админа университета
          <input name="admin_password" type="password" required minlength="8" autocomplete="new-password">
        </label>

        <button type="submit">Установить</button>
      </form>
    </div>
  <?php endif; ?>
</body>
</html>
