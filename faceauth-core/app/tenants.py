from fastapi import HTTPException
from app.store import get_tenant as store_get_tenant


def get_tenant(tenant_id: str) -> dict:
    tenant = store_get_tenant(tenant_id)
    if not tenant:
        raise HTTPException(status_code=404, detail="tenant_not_found")
    if tenant.get("status") == "suspended":
        raise HTTPException(status_code=403, detail="tenant_suspended")
    return tenant
