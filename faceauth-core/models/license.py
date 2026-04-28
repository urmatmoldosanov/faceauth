from pydantic import BaseModel


class License(BaseModel):
    tenant_id: str
    status: str
    paid_until: str
    grace_until: str
