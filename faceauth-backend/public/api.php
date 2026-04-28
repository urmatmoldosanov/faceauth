<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Storage.php';
require_once __DIR__ . '/../src/LicenseCache.php';
require_once __DIR__ . '/../src/FaceAuthClient.php';

header('Content-Type: application/json');

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        throw new RuntimeException('Empty body');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON');
    }

    return $decoded;
}

function verify_moodle_signature(string $raw, string $sharedSecret): bool
{
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    if ($signature === '') {
        return false;
    }

    $expected = hash_hmac('sha256', $raw, $sharedSecret);
    return hash_equals($expected, $signature);
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$storage = new Storage(
    Config::get('FACEAUTH_STORAGE_PHOTOS', __DIR__ . '/../storage/photos'),
    Config::get('FACEAUTH_STORAGE_LOGS', __DIR__ . '/../storage/logs')
);

$client = new FaceAuthClient(
    Config::require('FACEAUTH_CORE_URL'),
    Config::require('FACEAUTH_TENANT_ID'),
    Config::require('FACEAUTH_TENANT_SECRET')
);

$licenseCache = new LicenseCache(
    Config::get('FACEAUTH_LICENSE_CACHE', __DIR__ . '/../storage/license-cache.json')
);

if ($method === 'POST' && preg_match('#/snapshot$#', $path)) {
    try {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            throw new RuntimeException('Empty body');
        }

        $moodleSecret = Config::require('FACEAUTH_MOODLE_SHARED_SECRET');
        if (!verify_moodle_signature($raw, $moodleSecret)) {
            json_response(['status' => 'block', 'code' => 'invalid_signature'], 403);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON');
        }

        foreach (['attempt_id', 'quiz_id', 'cmid', 'event_time', 'image_base64'] as $required) {
            if (!isset($data[$required]) || $data[$required] === '') {
                json_response(['status' => 'warn', 'code' => 'invalid_payload'], 400);
            }
        }

        $photoPath = $storage->storeSnapshot((string)$data['attempt_id'], (string)$data['image_base64']);

        $verifyPayload = [
            'session_token' => (string)$data['attempt_id'],
            'match' => true,
            'score' => 0.95,
            'liveness' => 'unknown',
            'event_time' => (string)$data['event_time'],
            'meta' => [
                'quiz_id' => (string)$data['quiz_id'],
                'cmid' => (string)$data['cmid'],
            ],
        ];

        $coreResponse = $client->verifyResult($verifyPayload);

        $storage->appendLog([
            'time' => gmdate('c'),
            'attempt_id' => (string)$data['attempt_id'],
            'status' => $coreResponse['allow'] ?? 'warn',
            'code' => $coreResponse['reason_code'] ?? 'ok',
            'photo_path' => $photoPath,
        ]);

        if (isset($coreResponse['license'])) {
            $licenseCache->put($coreResponse['license']);
        }

        json_response([
            'status' => ($coreResponse['allow'] ?? false) ? 'allow' : 'warn',
            'code' => $coreResponse['reason_code'] ?? 'processed',
            'next_check_sec' => 15,
        ]);
    } catch (Throwable $e) {
        $storage->appendLog([
            'time' => gmdate('c'),
            'status' => 'error',
            'code' => 'snapshot_exception',
            'message' => $e->getMessage(),
        ]);
        json_response(['status' => 'warn', 'code' => 'internal_error'], 500);
    }
}

if ($method === 'GET' && preg_match('#/license/status$#', $path)) {
    json_response([
        'cached_license' => $licenseCache->get(),
    ]);
}

json_response(['status' => 'warn', 'code' => 'not_found'], 404);
