import secrets
from datetime import datetime, timezone
from fastapi import HTTPException
from app.db import db


def create_session(tenant_id: str, user_id: str, quiz_id: str, fio: str | None = None) -> dict:
    if tenant_id not in db.tenants:
        raise HTTPException(status_code=404, detail="tenant_not_found")

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
    db.sessions[token] = session
    return session


def get_session(token: str) -> dict:
    session = db.sessions.get(token)
    if not session:
        raise HTTPException(status_code=404, detail="session_not_found")
    return session


def finish_session(token: str) -> dict:
    session = get_session(token)
    session["status"] = "finished"
    session["ended_at"] = datetime.now(timezone.utc).isoformat()
    return session
