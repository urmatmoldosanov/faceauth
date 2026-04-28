from fastapi import APIRouter

router = APIRouter()


@router.post('/api/enroll/confirm')
async def enroll_confirm(payload: dict):
    return {"status": "accepted", "consent": bool(payload.get("consent"))}
