from __future__ import annotations

import secrets
from datetime import date
from pathlib import Path
from typing import Any

import pymysql
from fastapi import APIRouter, Form, Request, status
from fastapi.responses import HTMLResponse, RedirectResponse, Response
from fastapi.templating import Jinja2Templates
from pymysql.cursors import DictCursor

from app.admin_auth import (
    COOKIE_MAX_AGE,
    COOKIE_NAME,
    create_session_cookie,
    get_csrf_token,
    read_session,
    using_dev_credentials,
    verify_credentials,
    verify_csrf_token,
)
from app.config import settings

router = APIRouter(prefix="/admin", tags=["admin"])
templates = Jinja2Templates(directory=str(Path(__file__).resolve().parent.parent / "templates"))


def db_connection() -> pymysql.connections.Connection:
    return pymysql.connect(
        host=settings.db_host,
        port=settings.db_port,
        user=settings.db_user,
        password=settings.db_password,
        database=settings.db_name,
        charset="utf8mb4",
        cursorclass=DictCursor,
        autocommit=True,
    )


def fetch_all(sql: str, params: tuple[Any, ...] = ()) -> list[dict[str, Any]]:
    with db_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute(sql, params)
            return list(cursor.fetchall())


def fetch_one(sql: str, params: tuple[Any, ...] = ()) -> dict[str, Any] | None:
    rows = fetch_all(sql, params)
    return rows[0] if rows else None


def execute(sql: str, params: tuple[Any, ...] = ()) -> int:
    with db_connection() as conn:
        with conn.cursor() as cursor:
            cursor.execute(sql, params)
            return int(cursor.lastrowid or cursor.rowcount)


def admin_context(request: Request, **extra: Any) -> dict[str, Any]:
    context = {
        "request": request,
        "title": "FaceAuth Core Admin",
        "csrf_token": get_csrf_token(request),
    }
    context.update(extra)
    return context


def redirect_to_login() -> RedirectResponse:
    return RedirectResponse(url="/admin/login", status_code=status.HTTP_303_SEE_OTHER)


def require_admin(request: Request) -> dict[str, Any] | RedirectResponse:
    session = read_session(request)
    if not session:
        return redirect_to_login()
    return session


def require_post_admin(request: Request, csrf_token: str) -> dict[str, Any] | Response:
    session = read_session(request)
    if not session:
        return redirect_to_login()
    if not verify_csrf_token(request, csrf_token):
        return error_response(request, "Invalid CSRF token.", status.HTTP_400_BAD_REQUEST)
    return session


def error_response(
    request: Request, message: str = "Admin action failed.", code: int = 500
) -> HTMLResponse:
    return templates.TemplateResponse(
        "admin/error.html",
        admin_context(request, error_message=message),
        status_code=code,
    )


def normalize_optional_date(value: str | None) -> str | None:
    value = (value or "").strip()
    return value or None


def generated_license_key(tenant_id: str) -> str:
    return f"LIC-{tenant_id}-{date.today():%Y%m%d}-{secrets.token_hex(4).upper()}"


@router.get("", include_in_schema=False)
def admin_root(request: Request) -> Response:
    if not read_session(request):
        return redirect_to_login()
    return RedirectResponse(url="/admin/dashboard", status_code=status.HTTP_303_SEE_OTHER)


@router.get("/login", response_class=HTMLResponse, include_in_schema=False)
def login_page(request: Request) -> Response:
    if read_session(request):
        return RedirectResponse(url="/admin/dashboard", status_code=status.HTTP_303_SEE_OTHER)
    return templates.TemplateResponse("admin/login.html", {"request": request, "title": "FaceAuth Core Admin"})


@router.post("/login", include_in_schema=False)
def login_action(
    request: Request,
    username: str = Form(...),
    password: str = Form(...),
) -> Response:
    host = request.url.hostname or ""
    if using_dev_credentials() and host not in {"127.0.0.1", "localhost", "::1"}:
        return templates.TemplateResponse(
            "admin/login.html",
            {"request": request, "title": "FaceAuth Core Admin", "error": "Admin credentials are not configured for this host."},
            status_code=status.HTTP_403_FORBIDDEN,
        )
    if not verify_credentials(username, password):
        return templates.TemplateResponse(
            "admin/login.html",
            {"request": request, "title": "FaceAuth Core Admin", "error": "Invalid username or password."},
            status_code=status.HTTP_401_UNAUTHORIZED,
        )
    response = RedirectResponse(url="/admin/dashboard", status_code=status.HTTP_303_SEE_OTHER)
    response.set_cookie(
        COOKIE_NAME,
        create_session_cookie(username),
        max_age=COOKIE_MAX_AGE,
        httponly=True,
        samesite="lax",
        secure=request.url.scheme == "https",
    )
    return response


@router.get("/logout", include_in_schema=False)
def logout() -> Response:
    response = RedirectResponse(url="/admin/login", status_code=status.HTTP_303_SEE_OTHER)
    response.delete_cookie(COOKIE_NAME)
    return response


@router.get("/dashboard", response_class=HTMLResponse, include_in_schema=False)
def dashboard(request: Request) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    try:
        stats = {
            "tenants_total": fetch_one("SELECT COUNT(*) AS value FROM tenants")["value"],
            "tenants_active": fetch_one("SELECT COUNT(*) AS value FROM tenants WHERE status=%s", ("active",))["value"],
            "tenants_suspended": fetch_one("SELECT COUNT(*) AS value FROM tenants WHERE status=%s", ("suspended",))["value"],
            "sessions_total": fetch_one("SELECT COUNT(*) AS value FROM sessions")["value"],
            "sessions_active": fetch_one("SELECT COUNT(*) AS value FROM sessions WHERE status=%s", ("active",))["value"],
            "sessions_finished": fetch_one("SELECT COUNT(*) AS value FROM sessions WHERE status=%s", ("finished",))["value"],
            "verifications_total": fetch_one("SELECT COUNT(*) AS value FROM verifications")["value"],
        }
        verifications = fetch_all(
            "SELECT id, session_token, result, score, `timestamp` FROM verifications ORDER BY `timestamp` DESC, id DESC LIMIT 10"
        )
    except Exception:
        return error_response(request, "Unable to load dashboard data.")
    return templates.TemplateResponse(
        "admin/dashboard.html",
        admin_context(request, stats=stats, verifications=verifications),
    )


@router.get("/tenants", response_class=HTMLResponse, include_in_schema=False)
def tenants_page(request: Request) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    try:
        tenants = fetch_all(
            "SELECT id, name, domain, status, max_students, max_sessions FROM tenants ORDER BY id ASC"
        )
    except Exception:
        return error_response(request, "Unable to load tenants.")
    return templates.TemplateResponse("admin/tenants.html", admin_context(request, tenants=tenants))


@router.get("/tenants/create", response_class=HTMLResponse, include_in_schema=False)
def tenant_create_page(request: Request) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    return templates.TemplateResponse("admin/tenant_form.html", admin_context(request))


@router.post("/tenants/create", include_in_schema=False)
def tenant_create_action(
    request: Request,
    csrf_token: str = Form(...),
    tenant_id: str = Form(...),
    name: str = Form(...),
    domain: str = Form(...),
    max_students: int = Form(...),
    max_sessions: int = Form(...),
) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    tenant_id = tenant_id.strip()
    tenant_secret = secrets.token_urlsafe(48)
    try:
        execute(
            "INSERT INTO tenants (id, name, domain, status, tenant_secret, max_students, max_sessions) VALUES (%s, %s, %s, %s, %s, %s, %s)",
            (tenant_id, name.strip(), domain.strip(), "active", tenant_secret, max_students, max_sessions),
        )
    except Exception:
        return error_response(request, "Unable to create tenant.")
    return RedirectResponse(url=f"/admin/tenants/{tenant_id}", status_code=status.HTTP_303_SEE_OTHER)


@router.get("/tenants/{tenant_id}", response_class=HTMLResponse, include_in_schema=False)
def tenant_detail(request: Request, tenant_id: str) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    try:
        tenant = fetch_one(
            "SELECT id, name, domain, status, tenant_secret, max_students, max_sessions FROM tenants WHERE id=%s",
            (tenant_id,),
        )
        if not tenant:
            return error_response(request, "Tenant not found.", status.HTTP_404_NOT_FOUND)
        licenses = fetch_all(
            "SELECT id, tenant_id, license_key, status, paid_until, grace_until FROM licenses WHERE tenant_id=%s ORDER BY id DESC LIMIT 10",
            (tenant_id,),
        )
        sessions = fetch_all(
            "SELECT id, session_token, tenant_id, user_id, quiz_id, status, started_at, ended_at FROM sessions WHERE tenant_id=%s ORDER BY started_at DESC, id DESC LIMIT 10",
            (tenant_id,),
        )
    except Exception:
        return error_response(request, "Unable to load tenant.")
    return templates.TemplateResponse(
        "admin/tenant_detail.html",
        admin_context(request, tenant=tenant, licenses=licenses, sessions=sessions),
    )


@router.post("/tenants/{tenant_id}/activate", include_in_schema=False)
def tenant_activate(request: Request, tenant_id: str, csrf_token: str = Form(...)) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    try:
        execute("UPDATE tenants SET status=%s WHERE id=%s", ("active", tenant_id))
    except Exception:
        return error_response(request, "Unable to activate tenant.")
    return RedirectResponse(url=f"/admin/tenants/{tenant_id}", status_code=status.HTTP_303_SEE_OTHER)


@router.post("/tenants/{tenant_id}/suspend", include_in_schema=False)
def tenant_suspend(request: Request, tenant_id: str, csrf_token: str = Form(...)) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    try:
        execute("UPDATE tenants SET status=%s WHERE id=%s", ("suspended", tenant_id))
    except Exception:
        return error_response(request, "Unable to suspend tenant.")
    return RedirectResponse(url=f"/admin/tenants/{tenant_id}", status_code=status.HTTP_303_SEE_OTHER)


@router.post("/tenants/{tenant_id}/regenerate-secret", include_in_schema=False)
def tenant_regenerate_secret(request: Request, tenant_id: str, csrf_token: str = Form(...)) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    try:
        execute(
            "UPDATE tenants SET tenant_secret=%s WHERE id=%s",
            (secrets.token_urlsafe(48), tenant_id),
        )
    except Exception:
        return error_response(request, "Unable to regenerate tenant secret.")
    return RedirectResponse(url=f"/admin/tenants/{tenant_id}", status_code=status.HTTP_303_SEE_OTHER)


@router.get("/licenses", response_class=HTMLResponse, include_in_schema=False)
def licenses_page(request: Request) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    try:
        licenses = fetch_all(
            "SELECT id, tenant_id, license_key, status, paid_until, grace_until FROM licenses ORDER BY id DESC LIMIT 100"
        )
        tenants = fetch_all("SELECT id, name FROM tenants ORDER BY id ASC")
    except Exception:
        return error_response(request, "Unable to load licenses.")
    return templates.TemplateResponse(
        "admin/licenses.html", admin_context(request, licenses=licenses, tenants=tenants)
    )


@router.post("/licenses/create", include_in_schema=False)
def license_create_action(
    request: Request,
    csrf_token: str = Form(...),
    tenant_id: str = Form(...),
    license_key: str = Form(""),
    paid_until: str = Form(""),
    grace_until: str = Form(""),
    license_status: str = Form("active", alias="status"),
) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    tenant_id = tenant_id.strip()
    license_key = license_key.strip() or generated_license_key(tenant_id)
    try:
        execute(
            "INSERT INTO licenses (tenant_id, license_key, status, paid_until, grace_until) VALUES (%s, %s, %s, %s, %s)",
            (
                tenant_id,
                license_key,
                license_status.strip() or "active",
                normalize_optional_date(paid_until),
                normalize_optional_date(grace_until),
            ),
        )
    except Exception:
        return error_response(request, "Unable to create license.")
    return RedirectResponse(url=f"/admin/tenants/{tenant_id}", status_code=status.HTTP_303_SEE_OTHER)


@router.post("/licenses/{license_id}/activate", include_in_schema=False)
def license_activate(request: Request, license_id: int, csrf_token: str = Form(...)) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    try:
        tenant = fetch_one("SELECT tenant_id FROM licenses WHERE id=%s", (license_id,))
        if not tenant:
            return error_response(request, "License not found.", status.HTTP_404_NOT_FOUND)
        execute("UPDATE licenses SET status=%s WHERE id=%s", ("active", license_id))
    except Exception:
        return error_response(request, "Unable to activate license.")
    return RedirectResponse(url=f"/admin/tenants/{tenant['tenant_id']}", status_code=status.HTTP_303_SEE_OTHER)


@router.post("/licenses/{license_id}/suspend", include_in_schema=False)
def license_suspend(request: Request, license_id: int, csrf_token: str = Form(...)) -> Response:
    auth = require_post_admin(request, csrf_token)
    if isinstance(auth, Response):
        return auth
    try:
        tenant = fetch_one("SELECT tenant_id FROM licenses WHERE id=%s", (license_id,))
        if not tenant:
            return error_response(request, "License not found.", status.HTTP_404_NOT_FOUND)
        execute("UPDATE licenses SET status=%s WHERE id=%s", ("suspended", license_id))
    except Exception:
        return error_response(request, "Unable to suspend license.")
    return RedirectResponse(url=f"/admin/tenants/{tenant['tenant_id']}", status_code=status.HTTP_303_SEE_OTHER)


@router.get("/sessions", response_class=HTMLResponse, include_in_schema=False)
def sessions_page(request: Request) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    try:
        sessions = fetch_all(
            "SELECT id, session_token, tenant_id, user_id, quiz_id, status, started_at, ended_at FROM sessions ORDER BY started_at DESC, id DESC LIMIT 100"
        )
    except Exception:
        return error_response(request, "Unable to load sessions.")
    return templates.TemplateResponse("admin/sessions.html", admin_context(request, sessions=sessions))


@router.get("/verifications", response_class=HTMLResponse, include_in_schema=False)
def verifications_page(request: Request) -> Response:
    auth = require_admin(request)
    if isinstance(auth, Response):
        return auth
    try:
        verifications = fetch_all(
            "SELECT id, session_token, result, score, `timestamp` FROM verifications ORDER BY `timestamp` DESC, id DESC LIMIT 100"
        )
    except Exception:
        return error_response(request, "Unable to load verifications.")
    return templates.TemplateResponse(
        "admin/verifications.html", admin_context(request, verifications=verifications)
    )
