<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI only\n");
    exit(1);
}

$password = $argv[1] ?? null;
if ($password === null || $password === '') {
    fwrite(STDERR, "Usage: php scripts/generate_password_hash.php <password>\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
if ($hash === false) {
    fwrite(STDERR, "Could not generate password hash\n");
    exit(1);
}

fwrite(STDOUT, $hash . PHP_EOL);
