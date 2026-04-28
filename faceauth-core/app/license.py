from datetime import datetime, timezone
from fastapi import HTTPException
from app.store import get_license as store_get_license


def _parse(dt: str) -> datetime:
    return datetime.fromisoformat(dt)


def get_license(tenant_id: str) -> dict:
    lic = store_get_license(tenant_id)
    if not lic:
        raise HTTPException(status_code=404, detail="license_not_found")

    now = datetime.now(timezone.utc)
    paid_until = _parse(lic["paid_until"])
    grace_until = _parse(lic["grace_until"])

    if now > grace_until:
        lic["status"] = "suspended"

    if lic["status"] == "suspended":
        raise HTTPException(status_code=403, detail="license_inactive")

    return lic
