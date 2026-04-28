import hashlib
import hmac
from fastapi import Header, HTTPException


def verify_hmac_signature(body: bytes, secret: str, x_signature: str | None) -> None:
    if not x_signature:
        raise HTTPException(status_code=401, detail="missing_signature")

    expected = hmac.new(secret.encode("utf-8"), body, hashlib.sha256).hexdigest()
    if not hmac.compare_digest(expected, x_signature):
        raise HTTPException(status_code=401, detail="invalid_signature")


def require_tenant_id(x_tenant_id: str | None = Header(default=None)) -> str:
    if not x_tenant_id:
        raise HTTPException(status_code=401, detail="missing_tenant")
    return x_tenant_id
