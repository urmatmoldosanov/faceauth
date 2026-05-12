<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';

faceauth_require_user();

$name = basename((string)($_GET['file'] ?? ''));
if ($name === '' || $name === '.' || $name === '..') {
    http_response_code(400);
    echo 'Missing file';
    exit;
}

$photosDir = rtrim(Config::get('FACEAUTH_STORAGE_PHOTOS', __DIR__ . '/../../storage/photos'), '/');
$path = $photosDir . '/' . $name;

if (!is_file($path)) {
    http_response_code(404);
    echo 'Photo not found';
    exit;
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
