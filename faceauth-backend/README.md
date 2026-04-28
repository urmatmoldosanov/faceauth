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

4. Настройте document root на `faceauth-backend/public`.

## Endpoint'ы

- `GET /health`
- `GET /license/status`
- `POST /snapshot`
- `POST /violation`
- `GET /admin/index.php`

## Примечания по безопасности

- Всегда используйте HTTPS.
- Не храните секреты в репозитории.
- Для admin используйте только hash-пароли (`*_PASS_HASH`).
- Ограничьте доступ к `/admin/index.php` по IP или VPN.


## Deploy

См. `deploy/DEPLOY.md`, `deploy/nginx-faceauth-backend.conf`, `deploy/logrotate-faceauth-backend.conf`, `deploy/SYSTEMD_CHECKS.md`, `deploy/RUNBOOK.md` и smoke-test `scripts/smoke_test.sh`.


## Automation

```bash
make lint
make hash PASS="StrongPassword"
make env-check
make setup
make smoke URL="http://faceauth-backend.local"
make deploy-check URL="http://faceauth-backend.local"
```
