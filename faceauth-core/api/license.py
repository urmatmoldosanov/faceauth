from fastapi import APIRouter, Depends, HTTPException
from app.db import db
from app.auth import require_signed_request

router = APIRouter()


@router.post("/api/license/extend")
async def extend_license(payload: dict, signed_tenant_id: str = Depends(require_signed_request)):
    tenant_id = payload.get("tenant_id")
    if tenant_id != signed_tenant_id:
        raise HTTPException(status_code=403, detail="tenant_mismatch")

    paid_until = payload.get("paid_until")
    grace_until = payload.get("grace_until")

    lic = db.licenses.get(tenant_id)
    if not lic:
        raise HTTPException(status_code=404, detail="license_not_found")

    if paid_until:
        lic["paid_until"] = paid_until
    if grace_until:
        lic["grace_until"] = grace_until
    lic["status"] = "active"

    tenant = db.tenants.get(tenant_id)
    if tenant:
        tenant["status"] = "active"

    return {"status": "ok", "license": lic}
