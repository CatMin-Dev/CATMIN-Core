<?php

declare(strict_types=1);

namespace Core\security;

final class TrustedDeviceManager
{
    private const COOKIE_NAME = 'catmin_trusted_device';

    public function __construct(
        private readonly ?TrustedDeviceRepository $repository = null
    ) {
    }

    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $fingerprint = $this->fingerprintHash($token, $userId);

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $this->repository()->trustDevice($userId, $fingerprint, '', $this->clientIp(), $this->userAgent());

        return $token;
    }

    public function isTrusted(int $userId): bool
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($token) || $token === '') {
            return false;
        }

        $fingerprint = $this->fingerprintHash($token, $userId);
        $trusted = $this->repository()->findActiveDevice($userId, $fingerprint);
        if (!is_array($trusted)) {
            return false;
        }

        $this->repository()->touchLastSeen($userId, $fingerprint, $this->clientIp(), $this->userAgent());

        return true;
    }

    public function revokeCurrent(int $userId): bool
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($token) || $token === '') {
            return false;
        }

        return $this->revokeByToken($userId, $token);
    }

    public function revokeByToken(int $userId, string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        return $this->repository()->revokeDevice($userId, $this->fingerprintHash($token, $userId));
    }

    private function fingerprintHash(string $token, int $userId): string
    {
        return hash('sha256', $token . '|' . $userId);
    }

    private function repository(): TrustedDeviceRepository
    {
        return $this->repository ?? new TrustedDeviceRepository();
    }

    private function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && trim($ip) !== '' ? trim($ip) : null;
    }

    private function userAgent(): ?string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if (!is_string($userAgent)) {
            return null;
        }

        $userAgent = trim($userAgent);
        return $userAgent !== '' ? mb_substr($userAgent, 0, 255) : null;
    }
}
