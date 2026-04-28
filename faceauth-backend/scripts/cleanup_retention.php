<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Storage.php';

$days = isset($argv[1]) ? (int)$argv[1] : (int)(Config::get('FACEAUTH_RETENTION_DAYS', '30') ?? '30');
if ($days <= 0) {
    fwrite(STDERR, "Days must be > 0\n");
    exit(2);
}

$db = new Database(Config::get('FACEAUTH_DB_PATH', __DIR__ . '/../storage/faceauth.sqlite'));
$storage = new Storage(
    Config::get('FACEAUTH_STORAGE_PHOTOS', __DIR__ . '/../storage/photos'),
    Config::get('FACEAUTH_STORAGE_LOGS', __DIR__ . '/../storage/logs')
);

$result = $db->cleanupOldData($days);
$deletedFiles = $storage->deleteFiles($result['paths'] ?? []);
$db->addAudit('info', 'cleanup_cli', json_encode(['days' => $days, 'result' => $result, 'deleted_files' => $deletedFiles], JSON_UNESCAPED_UNICODE));

echo json_encode([
    'status' => 'ok',
    'days' => $days,
    'cutoff' => $result['cutoff'] ?? null,
    'snapshot_deleted' => $result['snapshot_deleted'] ?? 0,
    'violation_deleted' => $result['violation_deleted'] ?? 0,
    'deleted_files' => $deletedFiles,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
