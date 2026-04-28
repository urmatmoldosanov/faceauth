import secrets
from datetime import datetime, timezone
from fastapi import HTTPException
from app.store import upsert_session, get_session as store_get_session, finish_session as store_finish_session


def create_session(tenant_id: str, user_id: str, quiz_id: str, fio: str | None = None) -> dict:
    token = secrets.token_urlsafe(24)
    session = {
        "token": token,
        "tenant_id": tenant_id,
        "user_id": user_id,
        "quiz_id": quiz_id,
        "fio": fio,
        "status": "active",
        "started_at": datetime.now(timezone.utc).isoformat(),
    }
    upsert_session(session)
    return session


def get_session(token: str) -> dict:
    session = store_get_session(token)
    if not session:
        raise HTTPException(status_code=404, detail="session_not_found")
    return session


def finish_session(token: str) -> dict:
    session = store_finish_session(token)
    if not session:
        raise HTTPException(status_code=404, detail="session_not_found")
    return session
