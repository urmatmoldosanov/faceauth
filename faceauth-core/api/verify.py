from fastapi import APIRouter, Depends, HTTPException
from app.config import settings
from app.sessions import get_session
from app.tenants import get_tenant
from app.license import get_license
from app.recognition import evaluate_result
from models.verification import VerifyResultRequest, VerifyResultResponse
from services.token_service import issue_decision_token
from services.throttling import allow_request
from app.auth import require_signed_request

router = APIRouter()


@router.post("/api/verify/result", response_model=VerifyResultResponse)
async def verify_result(payload: VerifyResultRequest, signed_tenant_id: str = Depends(require_signed_request)):
    session = get_session(payload.session_token)
    tenant = get_tenant(session["tenant_id"])

    if tenant["id"] != signed_tenant_id:
        raise HTTPException(status_code=403, detail="tenant_mismatch")

    get_license(tenant["id"])

    if not allow_request(f"verify:{tenant['id']}", limit=120, window_seconds=60):
        return VerifyResultResponse(allow=False, reason_code="rate_limited", decision_token="")

    ok = payload.match and evaluate_result(payload.score, settings.verify_threshold)
    reason = "ok" if ok else "face_mismatch"
    token = issue_decision_token(payload.session_token, tenant["id"]) if ok else ""

    return VerifyResultResponse(allow=ok, reason_code=reason, decision_token=token)
