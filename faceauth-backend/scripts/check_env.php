<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Config.php';

$required = [
    'FACEAUTH_CORE_URL',
    'FACEAUTH_TENANT_ID',
    'FACEAUTH_TENANT_SECRET',
    'FACEAUTH_MOODLE_SHARED_SECRET',
    'FACEAUTH_ADMIN_USER',
    'FACEAUTH_ADMIN_PASS_HASH',
];

$missing = [];
foreach ($required as $key) {
    $value = Config::get($key);
    if ($value === null || $value === '') {
        $missing[] = $key;
        echo "MISSING: {$key}\n";
    } else {
        echo "OK: {$key}\n";
    }
}

if ($missing) {
    fwrite(STDERR, "Environment check failed\n");
    exit(1);
}

echo "Environment check passed\n";
