<?php

declare(strict_types=1);

final class Database
{
    private PDO $pdo;

    public function __construct(string $dbPath)
    {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }

        $this->pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS students (
            student_id TEXT PRIMARY KEY,
            full_name TEXT NOT NULL DEFAULT \'Unknown\',
            email TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS attempts (
            attempt_id TEXT PRIMARY KEY,
            quiz_id TEXT NOT NULL,
            cmid TEXT NOT NULL,
            student_id TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT \'active\',
            close_reason TEXT,
            started_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(student_id) REFERENCES students(student_id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS snapshots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            attempt_id TEXT NOT NULL,
            student_id TEXT NOT NULL,
            photo_path TEXT NOT NULL,
            verify_code TEXT,
            verify_status TEXT,
            event_time TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS violations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            attempt_id TEXT NOT NULL,
            student_id TEXT NOT NULL,
            code TEXT NOT NULL,
            details TEXT NOT NULL,
            event_time TEXT NOT NULL,
            photo_path TEXT,
            created_at TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            level TEXT NOT NULL,
            kind TEXT NOT NULL,
            details TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');
    }

    public function upsertStudent(string $studentId, string $fullName, ?string $email): void
    {
        $now = gmdate('c');
        $stmt = $this->pdo->prepare('INSERT INTO students(student_id, full_name, email, created_at, updated_at)
            VALUES (:student_id, :full_name, :email, :created_at, :updated_at)
            ON CONFLICT(student_id) DO UPDATE SET full_name = excluded.full_name, email = excluded.email, updated_at = excluded.updated_at');

        $stmt->execute([
            ':student_id' => $studentId,
            ':full_name' => $fullName !== '' ? $fullName : 'Unknown',
            ':email' => $email,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    public function upsertAttempt(string $attemptId, string $quizId, string $cmid, string $studentId, string $startedAt): void
    {
        $now = gmdate('c');
        $stmt = $this->pdo->prepare('INSERT INTO attempts(attempt_id, quiz_id, cmid, student_id, started_at, updated_at)
            VALUES (:attempt_id, :quiz_id, :cmid, :student_id, :started_at, :updated_at)
            ON CONFLICT(attempt_id) DO UPDATE SET quiz_id = excluded.quiz_id, cmid = excluded.cmid, student_id = excluded.student_id, updated_at = excluded.updated_at');

        $stmt->execute([
            ':attempt_id' => $attemptId,
            ':quiz_id' => $quizId,
            ':cmid' => $cmid,
            ':student_id' => $studentId,
            ':started_at' => $startedAt,
            ':updated_at' => $now,
        ]);
    }

    public function addSnapshot(string $attemptId, string $studentId, string $photoPath, string $eventTime, string $verifyStatus, string $verifyCode): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO snapshots(attempt_id, student_id, photo_path, verify_code, verify_status, event_time, created_at)
            VALUES (:attempt_id, :student_id, :photo_path, :verify_code, :verify_status, :event_time, :created_at)');

        $stmt->execute([
            ':attempt_id' => $attemptId,
            ':student_id' => $studentId,
            ':photo_path' => $photoPath,
            ':verify_code' => $verifyCode,
            ':verify_status' => $verifyStatus,
            ':event_time' => $eventTime,
            ':created_at' => gmdate('c'),
        ]);
    }

    public function addViolation(string $attemptId, string $studentId, string $code, string $details, string $eventTime, ?string $photoPath): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO violations(attempt_id, student_id, code, details, event_time, photo_path, created_at)
            VALUES (:attempt_id, :student_id, :code, :details, :event_time, :photo_path, :created_at)');

        $stmt->execute([
            ':attempt_id' => $attemptId,
            ':student_id' => $studentId,
            ':code' => $code,
            ':details' => $details,
            ':event_time' => $eventTime,
            ':photo_path' => $photoPath,
            ':created_at' => gmdate('c'),
        ]);
    }

    public function addAudit(string $level, string $kind, string $details): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_logs(level, kind, details, created_at)
            VALUES (:level, :kind, :details, :created_at)');

        $stmt->execute([
            ':level' => $level,
            ':kind' => $kind,
            ':details' => $details,
            ':created_at' => gmdate('c'),
        ]);
    }

    public function setAttemptStatus(string $attemptId, string $status, string $reason): void
    {
        $stmt = $this->pdo->prepare('UPDATE attempts SET status = :status, close_reason = :reason, updated_at = :updated_at WHERE attempt_id = :attempt_id');
        $stmt->execute([
            ':status' => $status,
            ':reason' => $reason,
            ':updated_at' => gmdate('c'),
            ':attempt_id' => $attemptId,
        ]);
    }

    public function studentsReport(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare('SELECT s.student_id, s.full_name, s.email,
            COALESCE(v.vcount, 0) AS violations,
            COALESCE(sn.scount, 0) AS snapshots,
            COALESCE(closed.closed_count, 0) AS closed_attempts,
            COALESCE(closed.blocked_count, 0) AS blocked_attempts,
            COALESCE(closed.cancelled_count, 0) AS cancelled_attempts
            FROM students s
            LEFT JOIN (
                SELECT student_id, COUNT(*) AS vcount FROM violations WHERE event_time BETWEEN :from AND :to GROUP BY student_id
            ) v ON v.student_id = s.student_id
            LEFT JOIN (
                SELECT student_id, COUNT(*) AS scount FROM snapshots WHERE event_time BETWEEN :from AND :to GROUP BY student_id
            ) sn ON sn.student_id = s.student_id
            LEFT JOIN (
                SELECT student_id,
                    SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) AS closed_count,
                    SUM(CASE WHEN status = "blocked" THEN 1 ELSE 0 END) AS blocked_count,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) AS cancelled_count
                FROM attempts GROUP BY student_id
            ) closed ON closed.student_id = s.student_id
            ORDER BY violations DESC, snapshots DESC, s.full_name ASC');

        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll() ?: [];
    }

    public function studentPortfolio(string $studentId, string $from, string $to): array
    {
        $snapStmt = $this->pdo->prepare('SELECT "snapshot" as kind, attempt_id, event_time, verify_code as code, verify_status as details, photo_path
            FROM snapshots WHERE student_id = :student_id AND event_time BETWEEN :from AND :to
            UNION ALL
            SELECT "violation" as kind, attempt_id, event_time, code, details, photo_path
            FROM violations WHERE student_id = :student_id AND event_time BETWEEN :from AND :to
            ORDER BY event_time DESC LIMIT 200');
        $snapStmt->execute([':student_id' => $studentId, ':from' => $from, ':to' => $to]);

        return $snapStmt->fetchAll() ?: [];
    }

    public function recentAudit(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM audit_logs ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function cleanupOldData(int $days): array
    {
        $cutoffTs = time() - ($days * 86400);
        $cutoff = gmdate('c', $cutoffTs);

        $paths = [];
        $pStmt = $this->pdo->prepare('SELECT photo_path FROM snapshots WHERE created_at < :cutoff');
        $pStmt->execute([':cutoff' => $cutoff]);
        foreach ($pStmt->fetchAll() ?: [] as $row) {
            if (is_string($row['photo_path'] ?? null)) {
                $paths[] = $row['photo_path'];
            }
        }

        $vStmt = $this->pdo->prepare('SELECT photo_path FROM violations WHERE created_at < :cutoff AND photo_path IS NOT NULL');
        $vStmt->execute([':cutoff' => $cutoff]);
        foreach ($vStmt->fetchAll() ?: [] as $row) {
            if (is_string($row['photo_path'] ?? null)) {
                $paths[] = $row['photo_path'];
            }
        }

        $snapDeleted = $this->pdo->prepare('DELETE FROM snapshots WHERE created_at < :cutoff');
        $snapDeleted->execute([':cutoff' => $cutoff]);

        $violDeleted = $this->pdo->prepare('DELETE FROM violations WHERE created_at < :cutoff');
        $violDeleted->execute([':cutoff' => $cutoff]);

        return [
            'cutoff' => $cutoff,
            'snapshot_deleted' => $snapDeleted->rowCount(),
            'violation_deleted' => $violDeleted->rowCount(),
            'paths' => array_values(array_unique($paths)),
        ];
    }
}
