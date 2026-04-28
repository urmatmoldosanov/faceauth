import hashlib
import hmac
from fastapi import Header, HTTPException, Request
from app.db import db


async def require_signed_request(
    request: Request,
    x_tenant_id: str | None = Header(default=None),
    x_signature: str | None = Header(default=None),
) -> str:
    if not x_tenant_id:
        raise HTTPException(status_code=401, detail="missing_tenant")

    tenant = db.tenants.get(x_tenant_id)
    if not tenant:
        raise HTTPException(status_code=404, detail="tenant_not_found")

    secret = tenant.get("secret")
    if not secret:
        raise HTTPException(status_code=500, detail="tenant_secret_missing")

    if not x_signature:
        raise HTTPException(status_code=401, detail="missing_signature")

    body = await request.body()
    expected = hmac.new(secret.encode("utf-8"), body, hashlib.sha256).hexdigest()
    if not hmac.compare_digest(expected, x_signature):
        raise HTTPException(status_code=401, detail="invalid_signature")

    return x_tenant_id
