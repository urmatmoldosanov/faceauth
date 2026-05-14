CREATE TABLE IF NOT EXISTS verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(190) NOT NULL,
    student_id VARCHAR(80) NOT NULL,
    course VARCHAR(190) NOT NULL,
    status VARCHAR(80) NOT NULL,
    risk_score TINYINT UNSIGNED NOT NULL,
    verified_at DATETIME NOT NULL,
    student_photo VARCHAR(500) NOT NULL,
    violation_photo VARCHAR(500) NOT NULL,
    evidence_note TEXT NOT NULL,
    INDEX verified_at_idx (verified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
