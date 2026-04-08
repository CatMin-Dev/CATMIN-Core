<?php

declare(strict_types=1);

final class CoreBackupDownloadToken
{
    /**
     * @return array{token:string,expires_at:int}
     */
    public function issue(int $ttlSeconds = 900): array
    {
        $ttlSeconds = max(120, min($ttlSeconds, 3600));
        return [
            'token' => bin2hex(random_bytes(24)),
            'expires_at' => time() + $ttlSeconds,
        ];
    }

    public function isValid(array $payload, string $token): bool
    {
        $expected = (string) ($payload['token'] ?? '');
        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expected === '' || $token === '') {
            return false;
        }
        if (!hash_equals($expected, $token)) {
            return false;
        }
        return $expiresAt > time();
    }
}
