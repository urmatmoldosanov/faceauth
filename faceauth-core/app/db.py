from datetime import datetime, timedelta, timezone


def _utcnow() -> datetime:
    return datetime.now(timezone.utc)


class InMemoryDB:
    def __init__(self) -> None:
        self.tenants = {
            "tenant_demo": {
                "id": "tenant_demo",
                "name": "Demo University",
                "status": "active",
                "secret": "tenant_demo_secret",
                "max_sessions": 1000,
            }
        }
        self.licenses = {
            "tenant_demo": {
                "tenant_id": "tenant_demo",
                "status": "active",
                "paid_until": (_utcnow() + timedelta(days=30)).isoformat(),
                "grace_until": (_utcnow() + timedelta(days=40)).isoformat(),
            }
        }
        self.sessions = {}
        self.verifications = []
        self.violations = []


db = InMemoryDB()
