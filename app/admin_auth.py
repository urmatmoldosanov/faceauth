import hmac
import os
import secrets
from typing import Any

from fastapi import Request
from itsdangerous import BadSignature, URLSafeSerializer

from app.config import settings

_ADMIN_USER_SET = "FACEAUTH_ADMIN_USER" in os.environ
_ADMIN_PASSWORD_SET = "FACEAUTH_ADMIN_PASSWORD" in os.environ


COOKIE_NAME = "faceauth_admin_session"
COOKIE_MAX_AGE = 60 * 60 * 8


def _serializer() -> URLSafeSerializer:
    return URLSafeSerializer(settings.admin_secret, salt="faceauth-admin-session")


def using_dev_credentials() -> bool:
    return not _ADMIN_USER_SET and not _ADMIN_PASSWORD_SET


def verify_credentials(username: str, password: str) -> bool:
    return hmac.compare_digest(username, settings.admin_user) and hmac.compare_digest(
        password, settings.admin_password
    )


def create_session_cookie(username: str) -> str:
    payload: dict[str, Any] = {
        "user": username,
        "csrf": secrets.token_urlsafe(32),
    }
    return _serializer().dumps(payload)


def read_session(request: Request) -> dict[str, Any] | None:
    raw_cookie = request.cookies.get(COOKIE_NAME)
    if not raw_cookie:
        return None
    try:
        payload = _serializer().loads(raw_cookie)
    except BadSignature:
        return None
    if not isinstance(payload, dict):
        return None
    if payload.get("user") != settings.admin_user:
        return None
    if not payload.get("csrf"):
        return None
    return payload


def get_csrf_token(request: Request) -> str:
    session = read_session(request)
    if not session:
        return ""
    return str(session.get("csrf", ""))


def verify_csrf_token(request: Request, token: str) -> bool:
    expected = get_csrf_token(request)
    return bool(expected) and hmac.compare_digest(expected, token or "")
