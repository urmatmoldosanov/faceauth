<?php

declare(strict_types=1);

final class Storage
{
    private string $photosDir;
    private string $logsDir;
    private string $sessionMapFile;

    public function __construct(string $photosDir, string $logsDir)
    {
        $this->photosDir = rtrim($photosDir, '/');
        $this->logsDir = rtrim($logsDir, '/');
        $this->sessionMapFile = $this->logsDir . '/session-map.json';

        if (!is_dir($this->photosDir)) {
            mkdir($this->photosDir, 0770, true);
        }

        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0770, true);
        }
    }

    public function storeSnapshot(string $attemptId, string $imageBase64): string
    {
        $clean = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $imageBase64);
        if (!is_string($clean)) {
            throw new RuntimeException('Invalid image payload');
        }

        $binary = base64_decode($clean, true);
        if ($binary === false || strlen($binary) === 0) {
            throw new RuntimeException('Could not decode image');
        }

        $name = sprintf('%s_%s.jpg', preg_replace('/[^a-zA-Z0-9_-]/', '', $attemptId), bin2hex(random_bytes(8)));
        $path = $this->photosDir . '/' . $name;
        file_put_contents($path, $binary);

        return $path;
    }

    public function appendLog(array $record): void
    {
        $file = $this->logsDir . '/events-' . date('Y-m-d') . '.log';
        file_put_contents($file, json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public function getSessionToken(string $attemptId): ?string
    {
        $map = $this->readSessionMap();
        $token = $map[$attemptId] ?? null;
        return is_string($token) && $token !== '' ? $token : null;
    }

    public function putSessionToken(string $attemptId, string $sessionToken): void
    {
        $map = $this->readSessionMap();
        $map[$attemptId] = $sessionToken;
        file_put_contents($this->sessionMapFile, json_encode($map, JSON_UNESCAPED_UNICODE));
    }

    private function readSessionMap(): array
    {
        if (!file_exists($this->sessionMapFile)) {
            return [];
        }

        $raw = file_get_contents($this->sessionMapFile);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
