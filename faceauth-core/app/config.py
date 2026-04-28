from pydantic import BaseModel
import os


class Settings(BaseModel):
    core_secret: str = os.getenv("FACEAUTH_CORE_SECRET", "change-me")
    verify_threshold: float = float(os.getenv("FACEAUTH_VERIFY_THRESHOLD", "0.6"))
    decision_ttl_seconds: int = int(os.getenv("FACEAUTH_DECISION_TTL", "30"))

    use_mysql: bool = os.getenv("FACEAUTH_USE_MYSQL", "0") == "1"
    mysql_host: str = os.getenv("FACEAUTH_MYSQL_HOST", "127.0.0.1")
    mysql_port: int = int(os.getenv("FACEAUTH_MYSQL_PORT", "3306"))
    mysql_user: str = os.getenv("FACEAUTH_MYSQL_USER", "root")
    mysql_password: str = os.getenv("FACEAUTH_MYSQL_PASSWORD", "")
    mysql_db: str = os.getenv("FACEAUTH_MYSQL_DB", "faceauth_core")


settings = Settings()
