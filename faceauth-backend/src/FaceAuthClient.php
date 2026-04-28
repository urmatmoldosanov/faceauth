<?php

declare(strict_types=1);

final class FaceAuthClient
{
    private string $baseUrl;
    private string $tenantId;
    private string $tenantSecret;

    public function __construct(string $baseUrl, string $tenantId, string $tenantSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tenantId = $tenantId;
        $this->tenantSecret = $tenantSecret;
    }

    public function verifyResult(array $payload): array
    {
        return $this->post('/api/verify/result', $payload);
    }

    private function post(string $path, array $payload): array
    {
        $body = json_encode($payload);
        if ($body === false) {
            throw new RuntimeException('Could not encode request payload');
        }

        $signature = hash_hmac('sha256', $body, $this->tenantSecret);

        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Tenant-Id: ' . $this->tenantId,
                'X-Signature: ' . $signature,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Core request failed: ' . $err);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid core response');
        }

        if ($code >= 400) {
            $decoded['http_code'] = $code;
        }

        return $decoded;
    }
}
