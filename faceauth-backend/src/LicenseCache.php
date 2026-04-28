<?php

declare(strict_types=1);

final class LicenseCache
{
    private string $cacheFile;

    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
    }

    public function get(): ?array
    {
        if (!is_file($this->cacheFile)) {
            return null;
        }

        $content = file_get_contents($this->cacheFile);
        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function put(array $license): void
    {
        file_put_contents($this->cacheFile, json_encode($license));
    }
}
