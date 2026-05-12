<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FaceAuth Backend</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.5; margin: 32px; max-width: 880px; }
    code { background: #f5f5f5; border-radius: 4px; padding: 2px 5px; }
    .card { border: 1px solid #ddd; border-radius: 8px; margin: 16px 0; padding: 16px; }
    .ok { color: #116b2c; font-weight: 700; }
    ul { padding-left: 22px; }
  </style>
</head>
<body>
  <h1>FaceAuth Backend</h1>
  <p class="ok">Web root настроен корректно. Для первичной настройки откройте installer, затем войдите в панель.</p>

  <div class="card">
    <h2>Что это за сайт?</h2>
    <p>
      Это PHP relay/admin слой FaceAuth. Moodle отправляет сюда snapshot/violation события,
      а backend пересылает verification данные в FaceAuth Core.
    </p>
  </div>

  <div class="card">
    <h2>Полезные ссылки</h2>
    <ul>
      <li><a href="/health">/health</a> — health check backend.</li>
      <li><a href="/license/status">/license/status</a> — cached license status.</li>
      <li><a href="/install.php">/install.php</a> — первичная установка пользователей и ролей.</li>
      <li><a href="/admin/index.php">/admin/index.php</a> — панель нарушений, ошибок, доказательств и пользователей.</li>
    </ul>
  </div>

  <div class="card">
    <h2>API endpoints для интеграции</h2>
    <ul>
      <li><code>POST /snapshot</code> — snapshots от Moodle plugin.</li>
      <li><code>POST /violation</code> — violation события от Moodle plugin.</li>
    </ul>
  </div>
</body>
</html>
