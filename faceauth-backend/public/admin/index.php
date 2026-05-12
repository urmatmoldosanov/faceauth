<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/LicenseCache.php';

$adminUser = Config::require('FACEAUTH_ADMIN_USER');
$adminPassHash = Config::require('FACEAUTH_ADMIN_PASS_HASH');
$viewerUser = Config::get('FACEAUTH_VIEWER_USER');
$viewerPassHash = Config::get('FACEAUTH_VIEWER_PASS_HASH');

$providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

$role = null;
if ($providedUser === $adminUser && password_verify($providedPass, $adminPassHash)) {
    $role = 'admin';
}

if ($role === null && $viewerUser !== null && $viewerPassHash !== null && $providedUser === $viewerUser && password_verify($providedPass, $viewerPassHash)) {
    $role = 'viewer';
}

if ($role === null) {
    header('WWW-Authenticate: Basic realm="FaceAuth Backend Admin"');
    http_response_code(401);
    echo 'Authentication required';
    exit;
}

$logsDir = Config::get('FACEAUTH_STORAGE_LOGS', __DIR__ . '/../../storage/logs');
$cacheFile = Config::get('FACEAUTH_LICENSE_CACHE', __DIR__ . '/../../storage/license-cache.json');

$cache = new LicenseCache($cacheFile);
$license = $cache->get();

$logFiles = glob(rtrim($logsDir, '/') . '/events-*.log') ?: [];
rsort($logFiles);
$latest = $logFiles[0] ?? null;
$lines = [];

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

        if ($kindFilter !== '' && (($decoded['kind'] ?? '') !== $kindFilter)) {
            continue;
        }

        if ($attemptFilter !== '' && (($decoded['attempt_id'] ?? '') !== $attemptFilter)) {
            continue;
        }

        $lines[] = $line;
        if (count($lines) >= 100) {
            break;
        }
    }
}
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FaceAuth Backend Admin</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    code, pre { background: #f5f5f5; padding: 8px; display: block; overflow: auto; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
    form { display: flex; gap: 8px; align-items: end; flex-wrap: wrap; }
    label { display: flex; flex-direction: column; gap: 4px; }
  </style>
</head>
<body>
  <h1>FaceAuth Backend Admin</h1>

  <div class="card">
    <strong>Role:</strong> <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
  </div>

  <?php if ($role === 'admin'): ?>
  <div class="card">
    <h2>License cache</h2>
    <pre><?= htmlspecialchars(json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: 'null', ENT_QUOTES, 'UTF-8') ?></pre>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Latest log file</h2>
    <code><?= htmlspecialchars($latest ?: 'No log file yet', ENT_QUOTES, 'UTF-8') ?></code>
  </div>

  <div class="card">
    <h2>Filters</h2>
    <form method="get">
      <label>
        kind
        <input type="text" name="kind" value="<?= htmlspecialchars($kindFilter, ENT_QUOTES, 'UTF-8') ?>" placeholder="snapshot|violation|error">
      </label>
      <label>
        attempt_id
        <input type="text" name="attempt_id" value="<?= htmlspecialchars($attemptFilter, ENT_QUOTES, 'UTF-8') ?>" placeholder="attempt id">
      </label>
      <button type="submit">Apply</button>
    </form>
  </div>

  <div class="card">
    <h2>Last 100 events</h2>
    <pre><?php foreach ($lines as $line): ?><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "
" ?><?php endforeach; ?></pre>
  </div>
</body>
</html>
