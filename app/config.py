import os
from dataclasses import dataclass


@dataclass(frozen=True)
class Settings:
    db_host: str = os.getenv("DB_HOST", "127.0.0.1")
    db_port: int = int(os.getenv("DB_PORT", "3306"))
    db_user: str = os.getenv("DB_USER", "faceauth")
    db_password: str = os.getenv("DB_PASSWORD", "")
    db_name: str = os.getenv("DB_NAME", "faceauth")
    admin_user: str = os.getenv("FACEAUTH_ADMIN_USER", "admin")
    admin_password: str = os.getenv("FACEAUTH_ADMIN_PASSWORD", "change-me-admin")
    admin_secret: str = os.getenv(
        "FACEAUTH_ADMIN_SECRET", "change-me-random-admin-secret"
    )


settings = Settings()
