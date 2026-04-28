from datetime import datetime, timezone
from fastapi import HTTPException
from app.db import db


def _parse(dt: str) -> datetime:
    return datetime.fromisoformat(dt)


def get_license(tenant_id: str) -> dict:
    lic = db.licenses.get(tenant_id)
    if not lic:
        raise HTTPException(status_code=404, detail="license_not_found")

    now = datetime.now(timezone.utc)
    paid_until = _parse(lic["paid_until"])
    grace_until = _parse(lic["grace_until"])

    if now > grace_until:
        lic["status"] = "suspended"
        tenant = db.tenants.get(tenant_id)
        if tenant:
            tenant["status"] = "suspended"

    if lic["status"] == "suspended":
        raise HTTPException(status_code=403, detail="license_inactive")

    return lic
