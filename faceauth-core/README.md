# FaceAuth Core (MVP)

FastAPI core service для центральной логики: лицензии, tenants, policy decision, throttling, decision token.

## Запуск

```bash
pip install -r requirements.txt
uvicorn app.main:app --host 0.0.0.0 --port 8000
```

## Endpoint'ы

- `GET /health`
- `POST /api/session/start`
- `POST /api/session/finish`
- `POST /api/verify/result`
- `POST /api/license/extend`
- `POST /api/enroll/confirm`

## Безопасность запросов

Каждый POST должен содержать заголовки:

- `X-Tenant-Id`
- `X-Signature` = `HMAC_SHA256(raw_body, tenant_secret)`

Core проверяет подпись и соответствие `tenant_id` в payload.

## MySQL схема

См. `sql/schema.sql`.


## MySQL mode

По умолчанию используется in-memory store.

Для MySQL режима:

- примените `sql/schema.sql`
- задайте env:
  - `FACEAUTH_USE_MYSQL=1`
  - `FACEAUTH_MYSQL_HOST`
  - `FACEAUTH_MYSQL_PORT`
  - `FACEAUTH_MYSQL_USER`
  - `FACEAUTH_MYSQL_PASSWORD`
  - `FACEAUTH_MYSQL_DB`

Важно: в таблице `tenants` должен быть `tenant_secret` для проверки `X-Signature`.


### Быстрая инициализация MySQL

```bash
cp .env.example .env
export $(grep -v '^#' .env | xargs)
python scripts/init_mysql.py
```

После этого в БД будет `tenant_demo` с `tenant_secret=tenant_demo_secret`.
