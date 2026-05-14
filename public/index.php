<?php

declare(strict_types=1);

session_start();

$configFile = __DIR__ . '/../config/config.php';
$exampleConfigFile = __DIR__ . '/../config/config.example.php';

if (!file_exists($configFile)) {
    renderInstaller($configFile, $exampleConfigFile);
    exit;
}

$config = require $configFile;
ensureStorage($config);
$pdo = database($config);
bootstrapSchema($pdo, databaseDriver($config));
seedDemoData($pdo);

$action = $_GET['action'] ?? 'dashboard';
if ($action === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

if (!isLoggedIn()) {
    handleLogin($config);
    renderLogin($config);
    exit;
}

$students = $pdo->query('SELECT * FROM verifications ORDER BY verified_at DESC')->fetchAll(PDO::FETCH_ASSOC);
renderDashboard($config, $students);

function databaseDriver(array $config): string
{
    return (string) ($config['database']['driver'] ?? 'sqlite');
}

function database(array $config): PDO
{
    $database = $config['database'] ?? ['driver' => 'sqlite', 'path' => $config['database_path'] ?? __DIR__ . '/../storage/faceauth.sqlite'];
    $driver = (string) ($database['driver'] ?? 'sqlite');

    if ($driver === 'mysql') {
        $host = (string) ($database['host'] ?? '127.0.0.1');
        $port = (string) ($database['port'] ?? '3306');
        $name = (string) ($database['name'] ?? 'faceauth');
        $charset = (string) ($database['charset'] ?? 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        $pdo = new PDO($dsn, (string) ($database['user'] ?? ''), (string) ($database['password'] ?? ''));
        $pdo->exec("SET NAMES {$charset}");
    } else {
        $pdo = new PDO('sqlite:' . (string) ($database['path'] ?? __DIR__ . '/../storage/faceauth.sqlite'));
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function ensureStorage(array $config): void
{
    $database = $config['database'] ?? ['driver' => 'sqlite', 'path' => $config['database_path'] ?? __DIR__ . '/../storage/faceauth.sqlite'];
    $dirs = [$config['upload_path'] ?? __DIR__ . '/../storage/uploads'];

    if (($database['driver'] ?? 'sqlite') === 'sqlite') {
        $dirs[] = dirname((string) ($database['path'] ?? __DIR__ . '/../storage/faceauth.sqlite'));
    }

    foreach (array_unique($dirs) as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

function bootstrapSchema(PDO $pdo, string $driver): void
{
    if ($driver === 'mysql') {
        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS verifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_name VARCHAR(190) NOT NULL,
                student_id VARCHAR(80) NOT NULL,
                course VARCHAR(190) NOT NULL,
                status VARCHAR(80) NOT NULL,
                risk_score TINYINT UNSIGNED NOT NULL,
                verified_at DATETIME NOT NULL,
                student_photo VARCHAR(500) NOT NULL,
                violation_photo VARCHAR(500) NOT NULL,
                evidence_note TEXT NOT NULL,
                INDEX verified_at_idx (verified_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
        return;
    }

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS verifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_name TEXT NOT NULL,
            student_id TEXT NOT NULL,
            course TEXT NOT NULL,
            status TEXT NOT NULL,
            risk_score INTEGER NOT NULL,
            verified_at TEXT NOT NULL,
            student_photo TEXT NOT NULL,
            violation_photo TEXT NOT NULL,
            evidence_note TEXT NOT NULL
        )
    SQL);
}

function seedDemoData(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM verifications')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $studentPhoto = '/assets/img/student-placeholder.svg';
    $violationPhoto = '/assets/img/violation-placeholder.svg';
    $rows = [
        ['Анна Миронова', 'FA-2041', 'Компьютерное зрение', 'Проверка пройдена', 3, '2026-05-14 11:24:00', $studentPhoto, $violationPhoto, 'Лицо совпало, подозрительных действий не обнаружено.'],
        ['Дамир Садыков', 'FA-1187', 'Информационная безопасность', 'Проверка пройдена', 8, '2026-05-14 10:02:00', $studentPhoto, $violationPhoto, 'Повторная сверка подтверждена оператором.'],
        ['Екатерина Волкова', 'FA-3302', 'Математический анализ', 'Проверка пройдена', 1, '2026-05-13 18:46:00', $studentPhoto, $violationPhoto, 'Идентификация выполнена автоматически.'],
    ];

    $stmt = $pdo->prepare('INSERT INTO verifications (student_name, student_id, course, status, risk_score, verified_at, student_photo, violation_photo, evidence_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($rows as $row) {
        $stmt->execute($row);
    }
}

function handleLogin(array $config): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $user = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (hash_equals($config['admin_user'], $user) && password_verify($password, $config['admin_password_hash'])) {
        $_SESSION['admin'] = $user;
        header('Location: /');
        exit;
    }

    $_SESSION['login_error'] = 'Неверный логин или пароль.';
}

function isLoggedIn(): bool
{
    return isset($_SESSION['admin']);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderShell(string $title, callable $content): void
{
    ?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php $content(); ?>
</body>
</html><?php
}

function renderLogo(array $config): void
{
    ?>
    <div class="brand-lockup" aria-label="<?= e($config['brand_name']) ?>">
        <div class="brand-mark">FA</div>
        <div>
            <div class="brand-name"><?= e($config['brand_name']) ?></div>
            <div class="brand-tagline">Premium Exam Trust Platform</div>
        </div>
    </div>
    <?php
}

function renderLogin(array $config): void
{
    $error = $_SESSION['login_error'] ?? '';
    unset($_SESSION['login_error']);

    renderShell($config['app_name'] . ' — вход', function () use ($config, $error): void {
        ?>
        <main class="login-page">
            <section class="hero-card">
                <?php renderLogo($config); ?>
                <h1>Премиальная панель контроля экзаменов</h1>
                <p>Единый кабинет для проверки личности, доказательной базы и спокойного разбора спорных ситуаций.</p>
                <div class="hero-stats">
                    <span><strong>99.8%</strong> точность сверки</span>
                    <span><strong>24/7</strong> аудит доступа</span>
                </div>
            </section>
            <section class="login-card">
                <div class="card-eyebrow">Secure access</div>
                <h2>Вход администратора</h2>
                <p>Используйте учетную запись, заданную при установке.</p>
                <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="login-form">
                    <label>Логин<input name="username" autocomplete="username" required></label>
                    <label>Пароль<input name="password" type="password" autocomplete="current-password" required></label>
                    <button type="submit">Войти в панель</button>
                </form>
            </section>
        </main>
        <?php
    });
}

function renderDashboard(array $config, array $students): void
{
    $driverLabel = databaseDriver($config) === 'mysql' ? 'MySQL' : 'SQLite';

    renderShell($config['app_name'] . ' — проверки', function () use ($config, $students, $driverLabel): void {
        ?>
        <div class="app-shell">
            <aside class="sidebar">
                <?php renderLogo($config); ?>
                <nav>
                    <a class="active" href="/">Проверки</a>
                    <a href="#evidence">Доказательства</a>
                    <a href="#deploy">Установка</a>
                </nav>
                <div class="db-badge">База: <?= e($driverLabel) ?></div>
                <a class="logout" href="/?action=logout">Выйти</a>
            </aside>
            <main class="dashboard">
                <header class="dashboard-header">
                    <div>
                        <div class="card-eyebrow">Live verification registry</div>
                        <h1>Студенты, прошедшие проверку</h1>
                        <p>Фото студента и фото нарушения хранятся рядом с каждой записью, чтобы спорные ситуации решались фактами.</p>
                    </div>
                    <div class="summary-card"><strong><?= count($students) ?></strong><span>успешных проверок</span></div>
                </header>
                <section class="student-grid" id="evidence">
                    <?php foreach ($students as $student): ?>
                        <article class="student-card">
                            <div class="student-card__top">
                                <img src="<?= e($student['student_photo']) ?>" alt="Фото студента <?= e($student['student_name']) ?>">
                                <div>
                                    <span class="status-pill"><?= e($student['status']) ?></span>
                                    <h2><?= e($student['student_name']) ?></h2>
                                    <p><?= e($student['student_id']) ?> · <?= e($student['course']) ?></p>
                                </div>
                            </div>
                            <div class="evidence-row">
                                <figure>
                                    <img src="<?= e($student['student_photo']) ?>" alt="Эталонное фото студента">
                                    <figcaption>Фото студента</figcaption>
                                </figure>
                                <figure>
                                    <img src="<?= e($student['violation_photo']) ?>" alt="Фото нарушения или контрольный кадр">
                                    <figcaption>Фото нарушения</figcaption>
                                </figure>
                            </div>
                            <div class="student-meta">
                                <span>Риск: <?= (int) $student['risk_score'] ?>%</span>
                                <span><?= e($student['verified_at']) ?></span>
                            </div>
                            <p class="note"><?= e($student['evidence_note']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </section>
                <section class="deploy-card" id="deploy">
                    <div>
                        <div class="card-eyebrow">WordPress-like setup</div>
                        <h2>Простая установка на хостинг</h2>
                        <p>Выберите SQLite для установки без базы данных или MySQL для классического хостинга с отдельной БД — таблицы создаются автоматически.</p>
                    </div>
                    <span class="deploy-link">Инструкция в README.md</span>
                </section>
            </main>
        </div>
        <?php
    });
}

function renderInstaller(string $configFile, string $exampleConfigFile): void
{
    $message = '';
    $defaults = file_exists($exampleConfigFile) ? require $exampleConfigFile : defaultInstallerConfig();
    $form = installerFormDefaults($defaults);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form = installerFormDefaults($_POST);
        $adminPassword = (string) ($_POST['admin_password'] ?? '');

        if (strlen($adminPassword) < 8) {
            $message = 'Пароль должен быть не короче 8 символов.';
        } else {
            $config = buildInstallerConfig($form, $adminPassword);

            try {
                ensureStorage($config);
                $pdo = database($config);
                bootstrapSchema($pdo, databaseDriver($config));
                seedDemoData($pdo);
            } catch (Throwable $exception) {
                $message = 'Не удалось подключиться к базе данных: ' . $exception->getMessage();
            }

            if ($message === '') {
                if (!is_dir(dirname($configFile))) {
                    mkdir(dirname($configFile), 0775, true);
                }
                file_put_contents($configFile, serializeConfig($config));
                header('Location: /');
                exit;
            }
        }
    }

    renderShell('Установка FaceAuth', function () use ($form, $message): void {
        ?>
        <main class="installer-page">
            <section class="login-card installer-card">
                <div class="brand-mark large">FA</div>
                <div class="card-eyebrow">Первый запуск</div>
                <h1>Установка за 60 секунд</h1>
                <p>Как WordPress: выберите SQLite или MySQL, заполните форму — приложение проверит подключение и создаст таблицы.</p>
                <?php if ($message): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>
                <form method="post" class="login-form installer-form">
                    <label>Название системы<input name="app_name" value="<?= e($form['app_name']) ?>" required></label>
                    <label>Название бренда<input name="brand_name" value="<?= e($form['brand_name']) ?>" required></label>
                    <label>Логин администратора<input name="admin_user" value="<?= e($form['admin_user']) ?>" required></label>
                    <label>Пароль администратора<input name="admin_password" type="password" minlength="8" required></label>
                    <label>Тип базы данных
                        <select name="database_driver">
                            <option value="sqlite" <?= $form['database_driver'] === 'sqlite' ? 'selected' : '' ?>>SQLite — без отдельной БД</option>
                            <option value="mysql" <?= $form['database_driver'] === 'mysql' ? 'selected' : '' ?>>MySQL — для хостинга/production</option>
                        </select>
                    </label>
                    <div class="installer-db-grid">
                        <label>MySQL host<input name="mysql_host" value="<?= e($form['mysql_host']) ?>"></label>
                        <label>MySQL port<input name="mysql_port" value="<?= e($form['mysql_port']) ?>"></label>
                        <label>Database<input name="mysql_name" value="<?= e($form['mysql_name']) ?>"></label>
                        <label>User<input name="mysql_user" value="<?= e($form['mysql_user']) ?>"></label>
                        <label class="installer-wide">Password<input name="mysql_password" type="password" value="<?= e($form['mysql_password']) ?>"></label>
                    </div>
                    <button type="submit">Установить FaceAuth</button>
                </form>
            </section>
        </main>
        <?php
    });
}

function defaultInstallerConfig(): array
{
    return [
        'app_name' => 'FaceAuth Premium',
        'brand_name' => 'FaceAuth',
        'database' => ['driver' => 'sqlite', 'path' => __DIR__ . '/../storage/faceauth.sqlite'],
        'upload_path' => __DIR__ . '/../storage/uploads',
        'admin_user' => 'admin',
    ];
}

function installerFormDefaults(array $source): array
{
    $database = $source['database'] ?? [];
    return [
        'app_name' => trim((string) ($source['app_name'] ?? 'FaceAuth Premium')) ?: 'FaceAuth Premium',
        'brand_name' => trim((string) ($source['brand_name'] ?? 'FaceAuth')) ?: 'FaceAuth',
        'admin_user' => trim((string) ($source['admin_user'] ?? 'admin')) ?: 'admin',
        'database_driver' => (string) ($source['database_driver'] ?? $database['driver'] ?? 'sqlite'),
        'mysql_host' => trim((string) ($source['mysql_host'] ?? $database['host'] ?? '127.0.0.1')) ?: '127.0.0.1',
        'mysql_port' => trim((string) ($source['mysql_port'] ?? $database['port'] ?? '3306')) ?: '3306',
        'mysql_name' => trim((string) ($source['mysql_name'] ?? $database['name'] ?? 'faceauth')) ?: 'faceauth',
        'mysql_user' => trim((string) ($source['mysql_user'] ?? $database['user'] ?? '')),
        'mysql_password' => (string) ($source['mysql_password'] ?? $database['password'] ?? ''),
    ];
}

function buildInstallerConfig(array $form, string $adminPassword): array
{
    $database = ['driver' => $form['database_driver'] === 'mysql' ? 'mysql' : 'sqlite'];

    if ($database['driver'] === 'mysql') {
        $database += [
            'host' => $form['mysql_host'],
            'port' => $form['mysql_port'],
            'name' => $form['mysql_name'],
            'user' => $form['mysql_user'],
            'password' => $form['mysql_password'],
            'charset' => 'utf8mb4',
        ];
    } else {
        $database['path'] = __DIR__ . '/../storage/faceauth.sqlite';
    }

    return [
        'app_name' => $form['app_name'],
        'brand_name' => $form['brand_name'],
        'database' => $database,
        'upload_path' => __DIR__ . '/../storage/uploads',
        'admin_user' => $form['admin_user'],
        'admin_password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
    ];
}

function serializeConfig(array $config): string
{
    return "<?php\n\nreturn " . var_export($config, true) . ";\n";
}
