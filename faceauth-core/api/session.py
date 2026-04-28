from fastapi import APIRouter
from models.session import StartSessionRequest, StartSessionResponse
from app.sessions import create_session, finish_session

router = APIRouter()


@router.post("/api/session/start", response_model=StartSessionResponse)
async def start_session(payload: StartSessionRequest):
    session = create_session(payload.tenant_id, payload.user_id, payload.quiz_id, payload.fio)
    return StartSessionResponse(session_token=session["token"])


@router.post("/api/session/finish")
async def finish(payload: dict):
    token = payload.get("session_token")
    session = finish_session(token)
    return {"status": session["status"]}
