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
