from fastapi import APIRouter, Depends, HTTPException
from models.session import StartSessionRequest, StartSessionResponse
from app.sessions import create_session, finish_session
from app.auth import require_signed_request

router = APIRouter()


@router.post("/api/session/start", response_model=StartSessionResponse)
async def start_session(payload: StartSessionRequest, signed_tenant_id: str = Depends(require_signed_request)):
    if payload.tenant_id != signed_tenant_id:
        raise HTTPException(status_code=403, detail="tenant_mismatch")
    session = create_session(payload.tenant_id, payload.user_id, payload.quiz_id, payload.fio)
    return StartSessionResponse(session_token=session["token"])


@router.post("/api/session/finish")
async def finish(payload: dict, signed_tenant_id: str = Depends(require_signed_request)):
    token = payload.get("session_token")
    session = finish_session(token)
    if session["tenant_id"] != signed_tenant_id:
        raise HTTPException(status_code=403, detail="tenant_mismatch")
    return {"status": session["status"]}
