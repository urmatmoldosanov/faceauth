<?php

declare(strict_types=1);

final class Config
{
    private static ?array $dotenv = null;

    private static function loadDotenv(): array
    {
        if (self::$dotenv !== null) {
            return self::$dotenv;
        }

        $result = [];
        $root = dirname(__DIR__);
        $envPath = $root . '/.env';

        if (is_file($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || substr($trimmed, 0, 1) === '#') {
                    continue;
                }

                $parts = explode('=', $trimmed, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $value = trim($value, "\"'");

                if ($key !== '') {
                    $result[$key] = $value;
                }
            }
        }

        self::$dotenv = $result;
        return self::$dotenv;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        $dotenv = self::loadDotenv();
        if (isset($dotenv[$key]) && $dotenv[$key] !== '') {
            return $dotenv[$key];
        }

        return $default;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null) {
            throw new RuntimeException("Missing required config: {$key}");
        }

        return $value;
    }
}
