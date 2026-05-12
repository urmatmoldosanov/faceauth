from pydantic import BaseModel


class VerifyResultRequest(BaseModel):
    session_token: str
    match: bool
    score: float
    liveness: str = "unknown"
    event_time: str
    meta: dict | None = None


class VerifyResultResponse(BaseModel):
    allow: bool
    reason_code: str
    decision_token: str
