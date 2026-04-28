<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Config.php';

session_start();
$role = $_SESSION['faceauth_role'] ?? null;
if (!is_string($role) || !in_array($role, ['admin', 'viewer'], true)) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$path = (string)($_GET['path'] ?? '');
if ($path === '') {
    http_response_code(400);
    echo 'Missing path';
    exit;
}

$photosDir = Config::get('FACEAUTH_STORAGE_PHOTOS', dirname(__DIR__, 2) . '/storage/photos') ?: '';
$realBase = realpath($photosDir);
$realFile = realpath($path);

if ($realBase === false || $realFile === false || strpos($realFile, $realBase) !== 0 || !is_file($realFile)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . (string)filesize($realFile));
readfile($realFile);
