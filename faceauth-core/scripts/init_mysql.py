#!/usr/bin/env python3
import os
from datetime import datetime, timedelta
from pathlib import Path

import pymysql


def env(name: str, default: str | None = None) -> str:
    value = os.getenv(name, default)
    if value is None:
        raise RuntimeError(f"Missing env var: {name}")
    return value


def main() -> None:
    conn = pymysql.connect(
        host=env("FACEAUTH_MYSQL_HOST", "127.0.0.1"),
        port=int(env("FACEAUTH_MYSQL_PORT", "3306")),
        user=env("FACEAUTH_MYSQL_USER", "root"),
        password=env("FACEAUTH_MYSQL_PASSWORD", ""),
        database=env("FACEAUTH_MYSQL_DB", "faceauth_core"),
        autocommit=True,
        cursorclass=pymysql.cursors.DictCursor,
    )

    schema = Path(__file__).resolve().parents[1] / "sql" / "schema.sql"
    sql = schema.read_text(encoding="utf-8")

    with conn:
        with conn.cursor() as cur:
            for statement in [s.strip() for s in sql.split(";") if s.strip()]:
                cur.execute(statement)

            cur.execute(
                """
                INSERT INTO tenants (id, name, domain, status, tenant_secret, max_students, max_sessions)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                  name=VALUES(name),
                  domain=VALUES(domain),
                  status=VALUES(status),
                  tenant_secret=VALUES(tenant_secret),
                  max_students=VALUES(max_students),
                  max_sessions=VALUES(max_sessions)
                """,
                ("tenant_demo", "Demo University", "demo.local", "active", "tenant_demo_secret", 10000, 1000),
            )

            paid_until = datetime.utcnow() + timedelta(days=30)
            grace_until = datetime.utcnow() + timedelta(days=40)

            cur.execute(
                """
                INSERT INTO licenses (tenant_id, license_key, status, paid_until, grace_until)
                VALUES (%s, %s, %s, %s, %s)
                """,
                ("tenant_demo", "LIC-DEMO-001", "active", paid_until, grace_until),
            )

    print("MySQL schema initialized and demo tenant seeded")


if __name__ == "__main__":
    main()
