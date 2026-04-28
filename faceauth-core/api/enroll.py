from fastapi import APIRouter, Depends
from app.auth import require_signed_request

router = APIRouter()


@router.post('/api/enroll/confirm')
async def enroll_confirm(payload: dict, signed_tenant_id: str = Depends(require_signed_request)):
    return {"status": "accepted", "consent": bool(payload.get("consent")), "tenant_id": signed_tenant_id}
