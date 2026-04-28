<?php

declare(strict_types=1);

$baseUrl = $argv[1] ?? 'http://faceauth-backend.local';

function get_status(string $url): int {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);

    @file_get_contents($url, false, $ctx);

    global $http_response_header;
    if (!is_array($http_response_header) || !isset($http_response_header[0])) {
        return 0;
    }

    if (preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        return (int)$m[1];
    }

    return 0;
}

$checks = [
    ['/health', 200],
    ['/license/status', 200],
    ['/admin/index.php', 401],
];

foreach ($checks as [$path, $expected]) {
    $code = get_status($baseUrl . $path);
    if ($code !== $expected) {
        fwrite(STDERR, "FAIL {$path}: expected {$expected}, got {$code}\n");
        exit(1);
    }
    echo "OK {$path}: {$code}\n";
}

echo "Smoke test passed\n";
