from pydantic import BaseModel
import os


class Settings(BaseModel):
    core_secret: str = os.getenv("FACEAUTH_CORE_SECRET", "change-me")
    verify_threshold: float = float(os.getenv("FACEAUTH_VERIFY_THRESHOLD", "0.6"))
    decision_ttl_seconds: int = int(os.getenv("FACEAUTH_DECISION_TTL", "30"))


settings = Settings()
