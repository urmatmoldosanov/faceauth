# FaceAuth — установка и настройка (MVP)

Документ покрывает 3 компонента:

1. `faceauth-core` (Python/FastAPI)
2. `faceauth-backend` (PHP relay/admin)
3. `quizaccess_faceauth` (Moodle plugin)

---

## 1) Установка FaceAuth Core

### 1.1 Требования
- Python 3.10+
- MySQL 8+
- Доступ к сети от backend до core

### 1.2 Настройка окружения

```bash
cd faceauth-core
cp .env.example .env
```

Отредактируйте `.env`:
- `FACEAUTH_CORE_SECRET`
- `FACEAUTH_VERIFY_THRESHOLD`
- `FACEAUTH_DECISION_TTL`
- `FACEAUTH_USE_MYSQL=1`
- `FACEAUTH_MYSQL_*`

### 1.3 Инициализация БД

Linux/macOS:

```bash
python scripts/init_mysql.py
```

Windows:

```bat
scripts\init_mysql_windows.bat
```

После инициализации создаётся demo tenant:
- `tenant_id=tenant_demo`
- `tenant_secret=tenant_demo_secret`

### 1.4 Запуск

Linux/macOS:

```bash
uvicorn app.main:app --host 0.0.0.0 --port 8000
```

Windows:

```bat
scripts\run_windows.bat
```

Проверка:

```bash
curl http://127.0.0.1:8000/health
```

---

## 2) Установка FaceAuth Backend (PHP)

### 2.1 Требования
- PHP 7.4+ (рекомендуется 8.1+)
- Веб-сервер (Apache/Nginx/shared hosting)
- Web root рекомендуется указывать на `faceauth-backend/public`; если нельзя, используйте корневые `index.php`/`install.php` и `.htaccess` в `faceauth-backend`

### 2.2 Конфигурация

Есть два варианта.

Вариант A — через web installer после привязки домена: откройте `/install.php`, заполните Core URL, Tenant ID/secret, Moodle shared secret и storage paths. Installer создаст `.env`.

Вариант B — вручную через файл:

```bash
cd faceauth-backend
cp install-config.example.env .env
```

Также можно использовать `.env.example` как короткий шаблон. Ключевые параметры в `.env`:
- `FACEAUTH_CORE_URL` (например `http://core-host:8000`)
- `FACEAUTH_TENANT_ID=tenant_demo`
- `FACEAUTH_TENANT_SECRET=tenant_demo_secret`
- `FACEAUTH_MOODLE_SHARED_SECRET=<общий секрет Moodle<->backend>`
- `FACEAUTH_ADMIN_USER`
- `FACEAUTH_ADMIN_PASS_HASH`

Hash получить так:

```bash
php scripts/generate_password_hash.php "StrongPassword"
```

### 2.3 Подготовка storage

Linux:

```bash
./scripts/setup_storage.sh
```

Shared hosting/Windows: вручную создайте папки:
- `faceauth-backend/storage/photos`
- `faceauth-backend/storage/logs`

### 2.4 Первичная установка панели

После привязки домена откройте:

```text
https://your-backend-domain/install.php
```

Заполните установочные конфиги:
- `FACEAUTH_CORE_URL`;
- `FACEAUTH_TENANT_ID`;
- `FACEAUTH_TENANT_SECRET`;
- `FACEAUTH_MOODLE_SHARED_SECRET`;
- storage paths для photos/logs/license cache/users.

Создайте:
- `superadmin` — управляет пользователями и ролями;
- `admin` университетской системы — смотрит нарушения, ошибки, license cache и фото-доказательства.

После этого откройте панель:

```text
https://your-backend-domain/admin/index.php
```

Панель показывает snapshots, violations, errors, фильтры по `attempt_id`, журнал событий и фото snapshots как доказательства.

### 2.5 Проверки

```bash
php scripts/check_env.php
php scripts/smoke_test.php https://your-backend-domain
```

---

## 3) Установка Moodle plugin `quizaccess_faceauth`

### 3.1 Копирование

Скопируйте plugin в Moodle:

```text
mod/quiz/accessrule/faceauth
```

### 3.2 Активация
1. Moodle admin → `Site administration -> Notifications`
2. Завершите установку

### 3.3 Настройки plugin
Укажите:
- Backend URL
- Tenant ID
- Shared secret (тот же `FACEAUTH_MOODLE_SHARED_SECRET`)
- Snapshot interval
- Minimum relay interval

### 3.4 Проверка
1. Откройте quiz attempt
2. Разрешите камеру
3. Убедитесь, что backend получает `snapshot`
4. Проверьте admin UI backend (`/admin/index.php`), нарушения/ошибки и фото-доказательства

---

## 4) Матрица секретов (обязательно)

- `Moodle plugin shared_secret` == `FACEAUTH_MOODLE_SHARED_SECRET` (backend)
- `FACEAUTH_TENANT_SECRET` (backend) == `tenant_secret` (core DB для tenant)
- `X-Tenant-Id` должен соответствовать tenant в core

---

## 5) Частые ошибки

- `invalid_signature`:
  - не совпадает shared secret;
  - подпись считается по другому payload.
- `tenant_mismatch`:
  - tenant в header/payload не совпадает.
- `license_inactive`:
  - истекли `paid_until/grace_until`.

---

## 6) Рекомендации перед продом

- Включить HTTPS везде
- Ограничить доступ к `/admin/index.php` (IP/VPN)
- Настроить ротацию логов
- Не хранить `.env` в public-директориях


Checklist перед продом: `PRODUCTION_READINESS_CHECKLIST.md`.


Релизный процесс: `RELEASE_PROCESS.md`.
