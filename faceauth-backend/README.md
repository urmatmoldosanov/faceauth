# FaceAuth Backend (MVP)

Минимальный PHP backend для приема snapshot/violation из Moodle relay и отправки verify-result в FaceAuth Core.

## Быстрый старт

1. Скопируйте переменные:

```bash
cp .env.example .env
```

2. Заполните значения в `.env` (или задайте как env vars в веб-сервере):
   - `FACEAUTH_CORE_URL`
   - `FACEAUTH_TENANT_ID`
   - `FACEAUTH_TENANT_SECRET`
   - `FACEAUTH_MOODLE_SHARED_SECRET`
   - `FACEAUTH_ADMIN_USER`
   - `FACEAUTH_ADMIN_PASS_HASH`
   - опционально `FACEAUTH_VIEWER_USER`, `FACEAUTH_VIEWER_PASS_HASH`

3. Сгенерируйте hash пароля:

```bash
php scripts/generate_password_hash.php "my-strong-password"
```

4. Рекомендуемый document root: `faceauth-backend/public`. Если shared hosting не даёт выбрать `public`, можно направить домен на `faceauth-backend`: корневой `index.php`, `install.php` и `.htaccess` прокинут запросы в `public/`, а `src/`, `storage/`, `deploy/`, `scripts/` будут закрыты правилами Apache.


## Первичная установка панели

1. Откройте `/install.php` после привязки домена.
2. Заполните установочные конфиги: Core URL, Tenant ID/secret, Moodle shared secret, пути storage/logs/cache/users.
3. Создайте `superadmin` — управляет пользователями и ролями.
4. Создайте `admin` университетской системы — смотрит нарушения, ошибки, license cache и evidence.
5. После установки войдите в `/admin/index.php`.

Если нужно подготовить конфиг вручную, используйте `install-config.example.env` как явный шаблон для `.env`.

Панель показывает счетчики snapshots/violations/errors, журнал событий, фильтры по типу и `attempt_id`, а также фото snapshots как доказательства.

## Endpoint'ы

- `GET /` (landing page)
- `GET /install.php` (первичная установка superadmin/admin)
- `GET /health`
- `GET /license/status`
- `POST /snapshot`
- `POST /violation`
- `GET /admin/index.php` (панель нарушений, ошибок, фото-доказательств и пользователей)
- `GET /admin/photo.php?file=...` (защищенный просмотр фото-доказательств)

## Примечания по безопасности

- Всегда используйте HTTPS.
- Не храните секреты в репозитории.
- Для admin используйте только hash-пароли (`*_PASS_HASH`).
- Ограничьте доступ к `/admin/index.php` по IP или VPN.


## Deploy

См. `deploy/DEPLOY.md`, `deploy/nginx-faceauth-backend.conf`, `deploy/logrotate-faceauth-backend.conf`, `deploy/SYSTEMD_CHECKS.md`, `deploy/RUNBOOK.md`, `deploy/WINDOWS_SHARED_HOSTING.md`, shell-smoke `scripts/smoke_test.sh` и PHP-smoke `scripts/smoke_test.php`.


## Automation

```bash
make lint
make hash PASS="StrongPassword"
make env-check
make setup
make smoke URL="http://faceauth-backend.local"
make deploy-check URL="http://faceauth-backend.local"
```


## Shared hosting notes (важно)

Если у вас обычный shared hosting, где нельзя удобно задать system env vars:

1. Создайте файл `faceauth-backend/.env` рядом с `.env.example`.
2. Заполните все `FACEAUTH_*` переменные.
3. Backend автоматически читает `.env` через `src/Config.php` (fallback, если env vars не заданы в системе).

Рекомендуется:
- запретить web-доступ к `.env` через правила хостинга;
- хранить `public/` как web root, а `src/`, `.env`, `storage/` — вне публичного доступа.


## Cross-platform CLI (PHP-only)

```bash
php scripts/check_env.php
php scripts/smoke_test.php http://faceauth-backend.local
```


Полный сценарий для Windows+shared-hosting: `../WINDOWS_FIRST_QUICKSTART.md`.


Полная инструкция: `INSTALL_AND_CONFIG.md`.


Checklist перед продом: `PRODUCTION_READINESS_CHECKLIST.md`.


CI: `.github/workflows/ci.yml` (core pytest + backend php lint).
