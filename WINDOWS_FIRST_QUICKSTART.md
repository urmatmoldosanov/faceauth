# FaceAuth Windows-first Quickstart

Этот сценарий рассчитан на:
- Moodle на Windows/IIS или Windows+Apache
- Core (Python/FastAPI) на Windows
- Backend (PHP) на shared hosting или Windows хостинге

## 1) FaceAuth Core (Windows)

Откройте `cmd` в `faceauth-core`:

```bat
copy .env.example .env
scripts\init_mysql_windows.bat
scripts\run_windows.bat
```

Проверьте:

```bat
curl http://127.0.0.1:8000/health
```

## 2) FaceAuth Backend (shared hosting / Windows)

1. Загрузите папку `faceauth-backend`.
2. Установите web root на `faceauth-backend/public`.
3. Создайте `faceauth-backend/.env` на основе `.env.example`.
4. Убедитесь, что:
   - `FACEAUTH_CORE_URL` указывает на Core,
   - `FACEAUTH_TENANT_ID=tenant_demo`,
   - `FACEAUTH_TENANT_SECRET=tenant_demo_secret`.

Проверки (CLI):

```bash
php scripts/check_env.php
php scripts/smoke_test.php https://your-backend-domain
```

## 3) Moodle plugin

Скопируйте плагин в:

```text
mod/quiz/accessrule/faceauth
```

Далее в Moodle:
1. `Site administration -> Notifications` (установка/обновление).
2. Включите `quizaccess_faceauth`.
3. Укажите:
   - Backend URL (endpoint backend snapshot relay)
   - Tenant ID
   - Shared secret
   - Snapshot interval

## 4) Быстрый E2E smoke

1. Откройте quiz attempt тестовым пользователем.
2. Разрешите доступ к камере.
3. Убедитесь, что backend получает `snapshot` события.
4. Проверьте в backend admin UI, что события пишутся в логи.

## 5) Частые проблемы

- `401 invalid_signature`:
  - не совпадает shared secret между Moodle relay и backend;
  - или tenant secret между backend и core.
- `tenant_mismatch`:
  - не совпадает `X-Tenant-Id` и `tenant_id` в payload/session.
- `license_inactive`:
  - проверьте `paid_until/grace_until` в Core.


Полная инструкция: `INSTALL_AND_CONFIG.md`.


Checklist перед продом: `PRODUCTION_READINESS_CHECKLIST.md`.
