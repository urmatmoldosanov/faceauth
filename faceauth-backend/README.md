# FaceAuth Backend (service + admin + DB)

PHP backend как отдельный сервис между Moodle и Core.

Что делает backend:
- принимает snapshots/violations от Moodle;
- хранит фото, фото нарушений и события в собственной БД SQLite;
- пишет журналы;
- отдает admin-панель со входом и отчетами по студентам;
- позволяет очищать старые снапшоты/нарушения по retention-политике.

## Быстрый старт

1. Скопируйте переменные:

```bash
cp .env.example .env
```

2. Заполните `.env`:
- `FACEAUTH_CORE_URL`
- `FACEAUTH_TENANT_ID`
- `FACEAUTH_TENANT_SECRET`
- `FACEAUTH_MOODLE_SHARED_SECRET`
- `FACEAUTH_DB_PATH`
- `FACEAUTH_RETENTION_DAYS`
- `FACEAUTH_ADMIN_USER`
- `FACEAUTH_ADMIN_PASS_HASH`

3. Сгенерируйте hash пароля:

```bash
php scripts/generate_password_hash.php "my-strong-password"
```

4. Настройте document root на `faceauth-backend/public`.

## Endpoint'ы

- `GET /health`
- `GET /license/status`
- `POST /snapshot`
- `POST /violation`
- `POST /maintenance/cleanup?days=30` (`X-Maintenance-Token`)
- `GET /admin/index.php`

## Admin-панель

- Вход через страницу логина (`/admin/index.php`), роли `admin` и `viewer`.
- Сводка по студентам за отчетный период: сколько snapshots/violations.
- Статусы попыток: `closed/blocked/cancelled`.
- Портфолио студента: фото snapshot и фото нарушений.
- Audit-журнал backend.

## Очистка старых данных

CLI:

```bash
php scripts/cleanup_retention.php 30
```

HTTP (для cron/автоматизации):

```bash
curl -X POST "https://backend.example.com/maintenance/cleanup?days=30" \
  -H "X-Maintenance-Token: <FACEAUTH_BACKEND_MAINTENANCE_TOKEN>"
```

## Automation

```bash
make lint
make hash PASS="StrongPassword"
make env-check
make setup
make smoke URL="http://faceauth-backend.local"
make cleanup DAYS=30
```
