<?php

declare(strict_types=1);

namespace Core\security;

final class TrustedDeviceManager
{
    private const COOKIE_NAME = 'catmin_trusted_device';

    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $fingerprint = hash('sha256', $token . '|' . $userId);

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $this->storeFingerprint($userId, $fingerprint);

        return $token;
    }

    public function isTrusted(int $userId): bool
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($token) || $token === '') {
            return false;
        }

        $fingerprint = hash('sha256', $token . '|' . $userId);
        $trusted = $this->loadTrusted();

        return in_array($fingerprint, $trusted[(string) $userId] ?? [], true);
    }

    private function storeFingerprint(int $userId, string $fingerprint): void
    {
        $trusted = $this->loadTrusted();
        $key = (string) $userId;
        $trusted[$key] = $trusted[$key] ?? [];

        if (!in_array($fingerprint, $trusted[$key], true)) {
            $trusted[$key][] = $fingerprint;
        }

        $path = CATMIN_STORAGE . '/security/trusted-devices.json';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode($trusted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function loadTrusted(): array
    {
        $path = CATMIN_STORAGE . '/security/trusted-devices.json';
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
