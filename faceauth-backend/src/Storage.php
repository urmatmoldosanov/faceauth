<?php

declare(strict_types=1);

final class Storage
{
    private string $photosDir;
    private string $logsDir;

    public function __construct(string $photosDir, string $logsDir)
    {
        $this->photosDir = rtrim($photosDir, '/');
        $this->logsDir = rtrim($logsDir, '/');

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
}
