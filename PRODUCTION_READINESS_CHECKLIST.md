# FaceAuth Production Readiness Checklist

Отметьте каждый пункт перед запуском в вузе.

## A. Core

- [ ] Core доступен по HTTPS и отвечает `GET /health`.
- [ ] Включен MySQL режим (`FACEAUTH_USE_MYSQL=1`).
- [ ] Применена актуальная схема `faceauth-core/sql/schema.sql`.
- [ ] В БД создан tenant и `tenant_secret`.
- [ ] Проверена HMAC-подпись (`X-Tenant-Id`, `X-Signature`) на POST endpoint'ах.
- [ ] Лицензия tenant активна (`paid_until/grace_until` валидны).

## B. Backend

- [ ] Web root указывает на `faceauth-backend/public`.
- [ ] `.env` заполнен и недоступен из web.
- [ ] Совпадают секреты Moodle↔Backend и Backend↔Core.
- [ ] Папки `storage/photos`, `storage/logs` созданы и доступны на запись.
- [ ] `/health` и `/license/status` отвечают 200.
- [ ] `/admin/index.php` закрыт auth (401 без учётки).

## C. Moodle plugin

- [ ] Plugin установлен в `mod/quiz/accessrule/faceauth`.
- [ ] В Moodle выставлены Backend URL / Tenant ID / Shared secret.
- [ ] Snapshot interval и min relay interval заданы.
- [ ] Камера в браузере запрашивается и работает.
- [ ] Snapshot события доходят до backend.

## D. Безопасность

- [ ] HTTPS включен на всех внешних endpoint'ах.
- [ ] Доступ к admin ограничен (IP/VPN + strong password hash).
- [ ] Логи ротируются, срок хранения данных определён.
- [ ] Резервные копии БД настроены.

## E. Операционные проверки

- [ ] Выполнен smoke test backend (`scripts/smoke_test.php` или `.sh`).
- [ ] Выполнен env-check backend (`scripts/check_env.php` или `.sh`).
- [ ] Проверена связка end-to-end: Moodle → Backend → Core.
- [ ] Проверен кейс ошибки подписи (`invalid_signature`).
- [ ] Проверен кейс просроченной лицензии (`license_inactive`).

## F. Готовность к инцидентам

- [ ] Назначен ответственный администратор.
- [ ] Есть контакты эскалации и SLA реакции.
- [ ] Runbook доступен (см. `faceauth-backend/deploy/RUNBOOK.md`).


Релизный процесс: `RELEASE_PROCESS.md`.
