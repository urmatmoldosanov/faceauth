import base64
import hashlib
import hmac
import json
from datetime import datetime, timedelta, timezone
from app.config import settings


def issue_decision_token(session_id: str, tenant_id: str) -> str:
    payload = {
        "session_id": session_id,
        "tenant_id": tenant_id,
        "exp": int((datetime.now(timezone.utc) + timedelta(seconds=settings.decision_ttl_seconds)).timestamp()),
    }
    body = json.dumps(payload, separators=(",", ":")).encode("utf-8")
    sig = hmac.new(settings.core_secret.encode("utf-8"), body, hashlib.sha256).hexdigest()
    token = base64.urlsafe_b64encode(body).decode("utf-8") + "." + sig
    return token
