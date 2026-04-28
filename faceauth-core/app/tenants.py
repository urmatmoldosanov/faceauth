from fastapi import HTTPException
from app.db import db


def get_tenant(tenant_id: str) -> dict:
    tenant = db.tenants.get(tenant_id)
    if not tenant:
        raise HTTPException(status_code=404, detail="tenant_not_found")
    if tenant.get("status") == "suspended":
        raise HTTPException(status_code=403, detail="tenant_suspended")
    return tenant
