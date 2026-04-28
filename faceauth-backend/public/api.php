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

function read_raw_body(): string
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        throw new RuntimeException('Empty body');
    }

    return $raw;
}

function decode_json(string $raw): array
{
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON');
    }

    return $decoded;
}

function verify_signature(string $raw, string $sharedSecret): bool
{
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    if ($signature === '') {
        return false;
    }

    $expected = hash_hmac('sha256', $raw, $sharedSecret);
    return hash_equals($expected, $signature);
}

function request_id(): string
{
    return bin2hex(random_bytes(6));
}

function respond_not_found(): void
{
    json_response(['status' => 'warn', 'code' => 'not_found'], 404);
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rid = request_id();

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

if ($method === 'GET' && preg_match('#/health$#', $path)) {
    json_response([
        'status' => 'ok',
        'request_id' => $rid,
        'time' => gmdate('c'),
    ]);
}

if ($method === 'GET' && preg_match('#/license/status$#', $path)) {
    json_response([
        'status' => 'ok',
        'request_id' => $rid,
        'cached_license' => $licenseCache->get(),
    ]);
}

if ($method === 'POST' && preg_match('#/violation$#', $path)) {
    try {
        $raw = read_raw_body();
        $moodleSecret = Config::require('FACEAUTH_MOODLE_SHARED_SECRET');
        if (!verify_signature($raw, $moodleSecret)) {
            json_response(['status' => 'block', 'code' => 'invalid_signature', 'request_id' => $rid], 403);
        }

        $data = decode_json($raw);
        foreach (['attempt_id', 'code', 'details', 'event_time'] as $required) {
            if (!isset($data[$required]) || $data[$required] === '') {
                json_response(['status' => 'warn', 'code' => 'invalid_payload', 'request_id' => $rid], 400);
            }
        }

        $storage->appendLog([
            'request_id' => $rid,
            'time' => gmdate('c'),
            'kind' => 'violation',
            'attempt_id' => (string)$data['attempt_id'],
            'code' => (string)$data['code'],
            'details' => (string)$data['details'],
            'event_time' => (string)$data['event_time'],
        ]);

        json_response(['status' => 'ok', 'request_id' => $rid]);
    } catch (Throwable $e) {
        $storage->appendLog([
            'request_id' => $rid,
            'time' => gmdate('c'),
            'kind' => 'error',
            'code' => 'violation_exception',
            'message' => $e->getMessage(),
        ]);
        json_response(['status' => 'warn', 'code' => 'internal_error', 'request_id' => $rid], 500);
    }
}

if ($method === 'POST' && preg_match('#/snapshot$#', $path)) {
    try {
        $raw = read_raw_body();
        $moodleSecret = Config::require('FACEAUTH_MOODLE_SHARED_SECRET');
        if (!verify_signature($raw, $moodleSecret)) {
            json_response(['status' => 'block', 'code' => 'invalid_signature', 'request_id' => $rid], 403);
        }

        $data = decode_json($raw);
        foreach (['attempt_id', 'quiz_id', 'cmid', 'event_time', 'image_base64'] as $required) {
            if (!isset($data[$required]) || $data[$required] === '') {
                json_response(['status' => 'warn', 'code' => 'invalid_payload', 'request_id' => $rid], 400);
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
            'request_id' => $rid,
            'time' => gmdate('c'),
            'kind' => 'snapshot',
            'attempt_id' => (string)$data['attempt_id'],
            'status' => $coreResponse['allow'] ?? false,
            'code' => $coreResponse['reason_code'] ?? 'ok',
            'photo_path' => $photoPath,
        ]);

        if (isset($coreResponse['license']) && is_array($coreResponse['license'])) {
            $licenseCache->put($coreResponse['license']);
        }

        json_response([
            'status' => ($coreResponse['allow'] ?? false) ? 'allow' : 'warn',
            'code' => $coreResponse['reason_code'] ?? 'processed',
            'next_check_sec' => 15,
            'request_id' => $rid,
        ]);
    } catch (Throwable $e) {
        $storage->appendLog([
            'request_id' => $rid,
            'time' => gmdate('c'),
            'kind' => 'error',
            'code' => 'snapshot_exception',
            'message' => $e->getMessage(),
        ]);
        json_response(['status' => 'warn', 'code' => 'internal_error', 'request_id' => $rid], 500);
    }
}

respond_not_found();
