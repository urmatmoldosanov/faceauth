<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/LicenseCache.php';
require_once __DIR__ . '/../../src/Database.php';

session_start();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$adminUser = Config::require('FACEAUTH_ADMIN_USER');
$adminPassHash = Config::require('FACEAUTH_ADMIN_PASS_HASH');
$viewerUser = Config::get('FACEAUTH_VIEWER_USER');
$viewerPassHash = Config::get('FACEAUTH_VIEWER_PASS_HASH');

if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/index.php');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $providedUser = trim((string)($_POST['username'] ?? ''));
    $providedPass = (string)($_POST['password'] ?? '');

    $role = null;
    if ($providedUser === $adminUser && password_verify($providedPass, $adminPassHash)) {
        $role = 'admin';
    }

    if ($role === null && $viewerUser !== null && $viewerPassHash !== null && $providedUser === $viewerUser && password_verify($providedPass, $viewerPassHash)) {
        $role = 'viewer';
    }

    if ($role !== null) {
        $_SESSION['faceauth_role'] = $role;
        $_SESSION['faceauth_user'] = $providedUser;
        header('Location: /admin/index.php');
        exit;
    }

    $error = 'Неверный логин или пароль';
}

$role = $_SESSION['faceauth_role'] ?? null;
$username = $_SESSION['faceauth_user'] ?? null;
if (!is_string($role) || !in_array($role, ['admin', 'viewer'], true)) {
    ?><!doctype html>
    <html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>FaceAuth Admin Login</title>
    <style>body{font-family:Arial,sans-serif;max-width:420px;margin:80px auto;padding:16px}.card{border:1px solid #ddd;border-radius:8px;padding:16px}label{display:block;margin:12px 0 6px}input{width:100%;padding:8px}.err{color:#b00020}</style>
    </head><body><div class="card"><h1>FaceAuth Admin</h1>
    <?php if ($error !== ''): ?><p class="err"><?= h($error) ?></p><?php endif; ?>
    <form method="post"><input type="hidden" name="action" value="login"><label>Логин</label><input name="username" required><label>Пароль</label><input name="password" type="password" required><br><br><button type="submit">Войти</button></form>
    </div></body></html><?php
    exit;
}

$db = new Database(Config::get('FACEAUTH_DB_PATH', __DIR__ . '/../../storage/faceauth.sqlite'));
$cache = new LicenseCache(Config::get('FACEAUTH_LICENSE_CACHE', __DIR__ . '/../../storage/license-cache.json'));
$license = $cache->get();

$from = trim((string)($_GET['from'] ?? gmdate('Y-m-01T00:00:00\Z')));
$to = trim((string)($_GET['to'] ?? gmdate('Y-m-d\T23:59:59\Z')));
$studentId = trim((string)($_GET['student_id'] ?? ''));
$students = $db->studentsReport($from, $to);
$portfolio = $studentId !== '' ? $db->studentPortfolio($studentId, $from, $to) : [];
$audit = $db->recentAudit(100);
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FaceAuth Backend Admin</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; background:#fafafa; }
    .card { border: 1px solid #ddd; background:#fff; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
    table { width:100%; border-collapse: collapse; font-size:14px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background:#f5f5f5; }
    .top { display:flex; justify-content:space-between; align-items:center; }
    .img { width:96px; height:72px; object-fit:cover; border:1px solid #ccc; }
    .meta { color:#555; font-size:12px; }
    input { padding:6px; }
  </style>
</head>
<body>
  <div class="top">
    <h1>FaceAuth Backend Admin</h1>
    <div>
      <strong><?= h((string)$username) ?></strong> (<?= h($role) ?>)
      <a href="/admin/index.php?action=logout">Выйти</a>
    </div>
  </div>

  <div class="card">
    <h2>Отчетный период</h2>
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
      <label>from<br><input type="text" name="from" value="<?= h($from) ?>"></label>
      <label>to<br><input type="text" name="to" value="<?= h($to) ?>"></label>
      <label>student_id<br><input type="text" name="student_id" value="<?= h($studentId) ?>" placeholder="опционально"></label>
      <button type="submit">Применить</button>
    </form>
  </div>

  <?php if ($role === 'admin'): ?>
  <div class="card">
    <h2>Лицензия</h2>
    <pre><?= h((string)(json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: 'null')) ?></pre>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Студенты (портфолио нарушений и снапшотов)</h2>
    <table>
      <thead><tr><th>student_id</th><th>ФИО</th><th>Email</th><th>Snapshots</th><th>Violations</th><th>Closed</th><th>Blocked</th><th>Cancelled</th></tr></thead>
      <tbody>
      <?php foreach ($students as $row): ?>
        <tr>
          <td><a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&student_id=<?= urlencode((string)$row['student_id']) ?>"><?= h((string)$row['student_id']) ?></a></td>
          <td><?= h((string)$row['full_name']) ?></td>
          <td><?= h((string)($row['email'] ?? '')) ?></td>
          <td><?= h((string)$row['snapshots']) ?></td>
          <td><?= h((string)$row['violations']) ?></td>
          <td><?= h((string)$row['closed_attempts']) ?></td>
          <td><?= h((string)$row['blocked_attempts']) ?></td>
          <td><?= h((string)$row['cancelled_attempts']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($studentId !== ''): ?>
  <div class="card">
    <h2>Портфолио студента: <?= h($studentId) ?></h2>
    <table>
      <thead><tr><th>Тип</th><th>Время</th><th>Попытка</th><th>Код</th><th>Детали</th><th>Фото</th></tr></thead>
      <tbody>
      <?php foreach ($portfolio as $row): ?>
        <tr>
          <td><?= h((string)$row['kind']) ?></td>
          <td><?= h((string)$row['event_time']) ?></td>
          <td><?= h((string)$row['attempt_id']) ?></td>
          <td><?= h((string)($row['code'] ?? '')) ?></td>
          <td><?= h((string)($row['details'] ?? '')) ?></td>
          <td>
            <?php $photoPath = (string)($row['photo_path'] ?? ''); ?>
            <?php if ($photoPath !== ''): ?>
              <?php $photoUrl = '/admin/photo.php?path=' . urlencode($photoPath); ?>
              <a href="<?= h($photoUrl) ?>" target="_blank"><img class="img" src="<?= h($photoUrl) ?>" alt="photo"></a>
            <?php else: ?>
              <span class="meta">нет фото</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Журнал backend (audit)</h2>
    <pre><?php foreach ($audit as $line): ?><?= h(json_encode($line, JSON_UNESCAPED_UNICODE) ?: '') . "\n" ?><?php endforeach; ?></pre>
  </div>
</body>
</html>
