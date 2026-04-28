import hashlib
import hmac
import json

from fastapi.testclient import TestClient

from app.main import app
from app.db import db


client = TestClient(app)
TENANT_ID = "tenant_demo"
TENANT_SECRET = "tenant_demo_secret"


def sign(payload: dict) -> dict:
    body = json.dumps(payload, separators=(",", ":")).encode("utf-8")
    signature = hmac.new(TENANT_SECRET.encode("utf-8"), body, hashlib.sha256).hexdigest()
    return {
        "X-Tenant-Id": TENANT_ID,
        "X-Signature": signature,
        "Content-Type": "application/json",
    }


def post(path: str, payload: dict):
    return client.post(path, headers=sign(payload), data=json.dumps(payload, separators=(",", ":")))


def reset_state():
    db.sessions.clear()
    db.verifications.clear()
    db.tenants[TENANT_ID]["status"] = "active"
    db.licenses[TENANT_ID]["status"] = "active"


def test_session_start_verify_finish_happy_path():
    reset_state()

    start_payload = {
        "tenant_id": TENANT_ID,
        "user_id": "u1",
        "fio": "Student One",
        "quiz_id": "quiz1",
    }
    r = post("/api/session/start", start_payload)
    assert r.status_code == 200
    token = r.json()["session_token"]

    verify_payload = {
        "session_token": token,
        "match": True,
        "score": 0.99,
        "liveness": "ok",
        "event_time": "2026-04-28T00:00:00Z",
        "meta": {"quiz_id": "quiz1"},
    }
    r2 = post("/api/verify/result", verify_payload)
    assert r2.status_code == 200
    assert r2.json()["allow"] is True
    assert r2.json()["decision_token"] != ""

    finish_payload = {"session_token": token}
    r3 = post("/api/session/finish", finish_payload)
    assert r3.status_code == 200
    assert r3.json()["status"] == "finished"


def test_invalid_signature():
    reset_state()
    payload = {"tenant_id": TENANT_ID, "user_id": "u2", "quiz_id": "quiz2"}
    bad_headers = {
        "X-Tenant-Id": TENANT_ID,
        "X-Signature": "bad",
        "Content-Type": "application/json",
    }
    r = client.post("/api/session/start", headers=bad_headers, data=json.dumps(payload))
    assert r.status_code == 401
    assert r.json()["detail"] == "invalid_signature"


def test_tenant_mismatch():
    reset_state()
    payload = {"tenant_id": "tenant_other", "user_id": "u3", "quiz_id": "quiz3"}
    r = post("/api/session/start", payload)
    assert r.status_code == 403
    assert r.json()["detail"] == "tenant_mismatch"


def test_license_inactive():
    reset_state()
    start_payload = {
        "tenant_id": TENANT_ID,
        "user_id": "u4",
        "fio": "Student Four",
        "quiz_id": "quiz4",
    }
    r = post("/api/session/start", start_payload)
    token = r.json()["session_token"]

    db.licenses[TENANT_ID]["status"] = "suspended"

    verify_payload = {
        "session_token": token,
        "match": True,
        "score": 0.99,
        "liveness": "ok",
        "event_time": "2026-04-28T00:00:00Z",
        "meta": {"quiz_id": "quiz4"},
    }
    r2 = post("/api/verify/result", verify_payload)
    assert r2.status_code == 403
    assert r2.json()["detail"] == "license_inactive"
