from __future__ import annotations

from datetime import datetime, timezone
from typing import Any

from app.config import settings
from app.db import db

try:
    import pymysql
except Exception:  # pragma: no cover - optional dependency at runtime
    pymysql = None


def _now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def _conn():
    if pymysql is None:
        raise RuntimeError("pymysql is not installed")
    return pymysql.connect(
        host=settings.mysql_host,
        port=settings.mysql_port,
        user=settings.mysql_user,
        password=settings.mysql_password,
        database=settings.mysql_db,
        autocommit=True,
        cursorclass=pymysql.cursors.DictCursor,
    )


def get_tenant(tenant_id: str) -> dict[str, Any] | None:
    if not settings.use_mysql:
        return db.tenants.get(tenant_id)

    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, name, status, tenant_secret AS secret, max_sessions FROM tenants WHERE id=%s", (tenant_id,))
            return cur.fetchone()


def get_license(tenant_id: str) -> dict[str, Any] | None:
    if not settings.use_mysql:
        return db.licenses.get(tenant_id)

    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT tenant_id, status, paid_until, grace_until FROM licenses WHERE tenant_id=%s ORDER BY id DESC LIMIT 1",
                (tenant_id,),
            )
            lic = cur.fetchone()
            if not lic:
                return None
            lic["paid_until"] = lic["paid_until"].isoformat()
            lic["grace_until"] = lic["grace_until"].isoformat()
            return lic


def upsert_session(session: dict[str, Any]) -> None:
    if not settings.use_mysql:
        db.sessions[session["token"]] = session
        return

    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO sessions (session_token, tenant_id, user_id, quiz_id, status, started_at)
                VALUES (%s, %s, %s, %s, %s, %s)
                """,
                (
                    session["token"],
                    session["tenant_id"],
                    session["user_id"],
                    session["quiz_id"],
                    session["status"],
                    datetime.fromisoformat(session["started_at"]),
                ),
            )


def get_session(token: str) -> dict[str, Any] | None:
    if not settings.use_mysql:
        return db.sessions.get(token)

    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT session_token, tenant_id, user_id, quiz_id, status, started_at, ended_at FROM sessions WHERE session_token=%s",
                (token,),
            )
            row = cur.fetchone()
            if not row:
                return None
            return {
                "token": row["session_token"],
                "tenant_id": row["tenant_id"],
                "user_id": row["user_id"],
                "quiz_id": row["quiz_id"],
                "status": row["status"],
                "started_at": row["started_at"].isoformat() if row["started_at"] else _now_iso(),
                "ended_at": row["ended_at"].isoformat() if row["ended_at"] else None,
            }


def finish_session(token: str) -> dict[str, Any] | None:
    if not settings.use_mysql:
        session = db.sessions.get(token)
        if not session:
            return None
        session["status"] = "finished"
        session["ended_at"] = _now_iso()
        return session

    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute("UPDATE sessions SET status='finished', ended_at=%s WHERE session_token=%s", (datetime.now(), token))
    return get_session(token)


def insert_verification(session_token: str, result: str, score: float) -> None:
    if not settings.use_mysql:
        db.verifications.append({"session_token": session_token, "result": result, "score": score, "timestamp": _now_iso()})
        return

    with _conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO verifications (session_token, result, score, timestamp) VALUES (%s,%s,%s,%s)",
                (session_token, result, score, datetime.now()),
            )
