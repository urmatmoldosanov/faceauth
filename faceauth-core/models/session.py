from pydantic import BaseModel


class StartSessionRequest(BaseModel):
    tenant_id: str
    user_id: str
    fio: str | None = None
    quiz_id: str


class StartSessionResponse(BaseModel):
    session_token: str
