CREATE TABLE IF NOT EXISTS verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_name TEXT NOT NULL,
    student_id TEXT NOT NULL,
    course TEXT NOT NULL,
    status TEXT NOT NULL,
    risk_score INTEGER NOT NULL,
    verified_at TEXT NOT NULL,
    student_photo TEXT NOT NULL,
    violation_photo TEXT NOT NULL,
    evidence_note TEXT NOT NULL
);
