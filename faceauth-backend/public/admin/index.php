<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/LicenseCache.php';

$user = faceauth_require_user();
$role = (string)($user['role'] ?? 'viewer');
$username = (string)($user['username'] ?? 'unknown');
$store = faceauth_user_store();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    if (!faceauth_can_manage_users($user)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    try {
        $store->addUser(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['password'] ?? ''),
            (string)($_POST['role'] ?? 'viewer')
        );
        $message = 'Пользователь создан';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$logsDir = Config::get('FACEAUTH_STORAGE_LOGS', __DIR__ . '/../../storage/logs');
$cacheFile = Config::get('FACEAUTH_LICENSE_CACHE', __DIR__ . '/../../storage/license-cache.json');

$cache = new LicenseCache($cacheFile);
$license = $cache->get();

$logFiles = glob(rtrim($logsDir, '/') . '/events-*.log') ?: [];
rsort($logFiles);
$latest = $logFiles[0] ?? null;
$events = [];
$stats = ['snapshot' => 0, 'violation' => 0, 'error' => 0, 'other' => 0];

$kindFilter = trim((string)($_GET['kind'] ?? ''));
$attemptFilter = trim((string)($_GET['attempt_id'] ?? ''));

if ($latest && is_file($latest)) {
    $raw = file($latest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $raw = array_reverse($raw);

    foreach ($raw as $line) {
        $decoded = json_decode($line, true);
        if (!is_array($decoded)) {
            continue;
        }

        $kind = (string)($decoded['kind'] ?? 'other');
        if (isset($stats[$kind])) {
            $stats[$kind]++;
        } else {
            $stats['other']++;
        }

        if ($kindFilter !== '' && $kind !== $kindFilter) {
            continue;
        }

        if ($attemptFilter !== '' && ((string)($decoded['attempt_id'] ?? '') !== $attemptFilter)) {
            continue;
        }

        $events[] = $decoded;
        if (count($events) >= 200) {
            break;
        }
    }
}

$users = $store->all();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function event_photo_name(array $event): string
{
    $path = (string)($event['photo_path'] ?? '');
    if ($path === '') {
        return '';
    }

    return basename($path);
}
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FaceAuth Backend Admin</title>
  <style>
    body { background: #f6f7fb; color: #18202a; font-family: Arial, sans-serif; margin: 24px; }
    a { color: #1559c7; }
    code, pre { background: #f5f5f5; padding: 8px; display: block; overflow: auto; }
    .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .card { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 14px; margin-bottom: 16px; }
    .metric { font-size: 28px; font-weight: 700; }
    .muted { color: #667085; }
    form { display: flex; gap: 8px; align-items: end; flex-wrap: wrap; }
    label { display: flex; flex-direction: column; gap: 4px; }
    input, select, button { font-size: 14px; padding: 8px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; vertical-align: top; }
    th { background: #f9fafb; }
    .badge { border-radius: 999px; display: inline-block; font-size: 12px; padding: 3px 8px; }
    .snapshot { background: #e8f2ff; }
    .violation { background: #fff0d5; }
    .error { background: #ffe2e2; }
    .other { background: #eeeeee; }
    .notice { background: #ecfff1; border-color: #9ed3aa; }
    .danger { background: #ffecec; border-color: #e5a0a0; }
    img.evidence { border: 1px solid #ddd; border-radius: 6px; max-height: 90px; max-width: 120px; }
  </style>
</head>
<body>
  <h1>FaceAuth Backend Admin</h1>

  <div class="card">
    <strong>Пользователь:</strong> <?= e($username) ?>
    <strong>Роль:</strong> <?= e($role) ?>
    <?php if (!$store->isInstalled()): ?>
      <span class="muted"> · используется legacy .env auth; для полноценной установки откройте <a href="/install.php">installer</a></span>
    <?php endif; ?>
  </div>

  <?php if ($message !== ''): ?><div class="card notice"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="card danger"><?= e($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card"><div class="muted">Snapshots</div><div class="metric"><?= (int)$stats['snapshot'] ?></div></div>
    <div class="card"><div class="muted">Violations</div><div class="metric"><?= (int)$stats['violation'] ?></div></div>
    <div class="card"><div class="muted">Errors</div><div class="metric"><?= (int)$stats['error'] ?></div></div>
    <div class="card"><div class="muted">Other</div><div class="metric"><?= (int)$stats['other'] ?></div></div>
  </div>

  <?php if (in_array($role, ['superadmin', 'admin'], true)): ?>
  <div class="card">
    <h2>License cache</h2>
    <pre><?= e(json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: 'null') ?></pre>
  </div>
  <?php endif; ?>

  <?php if (faceauth_can_manage_users($user)): ?>
  <div class="card">
    <h2>Пользователи и роли</h2>
    <table>
      <thead><tr><th>Логин</th><th>Роль</th><th>Создан</th></tr></thead>
      <tbody>
      <?php foreach ($users as $storedUser): ?>
        <tr>
          <td><?= e((string)($storedUser['username'] ?? '')) ?></td>
          <td><?= e((string)($storedUser['role'] ?? '')) ?></td>
          <td><?= e((string)($storedUser['created_at'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h3>Создать пользователя</h3>
    <form method="post">
      <input type="hidden" name="action" value="create_user">
      <label>Логин <input name="username" required></label>
      <label>Пароль <input name="password" type="password" required minlength="8"></label>
      <label>Роль
        <select name="role">
          <option value="admin">admin</option>
          <option value="viewer">viewer</option>
          <option value="superadmin">superadmin</option>
        </select>
      </label>
      <button type="submit">Создать</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Журнал нарушений, ошибок и snapshots</h2>
    <div class="muted">Файл: <?= e($latest ?: 'No log file yet') ?></div>
    <form method="get">
      <label>
        kind
        <select name="kind">
          <option value=""<?= $kindFilter === '' ? ' selected' : '' ?>>all</option>
          <option value="snapshot"<?= $kindFilter === 'snapshot' ? ' selected' : '' ?>>snapshot</option>
          <option value="violation"<?= $kindFilter === 'violation' ? ' selected' : '' ?>>violation</option>
          <option value="error"<?= $kindFilter === 'error' ? ' selected' : '' ?>>error</option>
        </select>
      </label>
      <label>
        attempt_id
        <input type="text" name="attempt_id" value="<?= e($attemptFilter) ?>" placeholder="attempt id">
      </label>
      <button type="submit">Apply</button>
    </form>
  </div>

  <div class="card">
    <h2>Последние события</h2>
    <table>
      <thead>
        <tr>
          <th>Время</th>
          <th>Тип</th>
          <th>Attempt</th>
          <th>Код/статус</th>
          <th>Доказательство</th>
          <th>Детали</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($events as $event): ?>
        <?php $kind = (string)($event['kind'] ?? 'other'); $photo = event_photo_name($event); ?>
        <tr>
          <td><?= e((string)($event['time'] ?? $event['event_time'] ?? '')) ?></td>
          <td><span class="badge <?= e(isset($stats[$kind]) ? $kind : 'other') ?>"><?= e($kind) ?></span></td>
          <td><?= e((string)($event['attempt_id'] ?? '')) ?></td>
          <td><?= e((string)($event['code'] ?? $event['status'] ?? '')) ?></td>
          <td>
            <?php if ($photo !== ''): ?>
              <a href="/admin/photo.php?file=<?= rawurlencode($photo) ?>" target="_blank"><img class="evidence" src="/admin/photo.php?file=<?= rawurlencode($photo) ?>" alt="snapshot evidence"></a>
            <?php else: ?>
              <span class="muted">нет фото</span>
            <?php endif; ?>
          </td>
          <td><pre><?= e(json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}') ?></pre></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
