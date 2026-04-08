<?php

declare(strict_types=1);

namespace Core\auth;

use Core\config\Config;
use PDO;

final class SessionManager
{
    private const AUTH_KEY = 'catmin_admin_auth';
    private const REAUTH_KEY = 'catmin_admin_reauth_at';

    public function __construct(private readonly PDO $pdo) {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) Config::get('security.admin_session_name', 'CATMIN_ADMIN_SESSID'));
        session_start();
    }

    public function login(int $userId, string $ipAddress, string $userAgent): void
    {
        $this->start();

        session_regenerate_id(true);
        $token = bin2hex(random_bytes(32));

        $_SESSION[self::AUTH_KEY] = [
            'user_id' => $userId,
            'token' => $token,
            'ip' => $ipAddress,
            'user_agent' => substr($userAgent, 0, 255),
            'logged_at' => time(),
            'last_activity' => time(),
        ];

        $_SESSION[self::REAUTH_KEY] = time();

        $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'sessions';
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . $table . ' (user_id, session_token, ip_address, user_agent, last_activity_at, created_at) VALUES (:user_id, :session_token, :ip_address, :user_agent, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'session_token' => $token,
            'ip_address' => substr($ipAddress, 0, 64),
            'user_agent' => substr($userAgent, 0, 255),
        ]);
    }

    public function isAuthenticated(): bool
    {
        $this->start();
        $auth = $_SESSION[self::AUTH_KEY] ?? null;

        if (!is_array($auth) || !isset($auth['user_id'], $auth['token'])) {
            return false;
        }

        $timeout = (int) Config::get('security.session_lifetime', 7200);
        $lastActivity = isset($auth['last_activity']) ? (int) $auth['last_activity'] : 0;
        if ($lastActivity <= 0 || (time() - $lastActivity) > $timeout) {
            $this->logout();
            return false;
        }

        if ((bool) Config::get('security.bind_session_fingerprint', true)) {
            $currentIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            $currentUa = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $savedIp = (string) ($auth['ip'] ?? '');
            $savedUa = (string) ($auth['user_agent'] ?? '');
            if ($savedIp !== '' && $currentIp !== '' && !hash_equals($savedIp, $currentIp)) {
                $this->logout();
                return false;
            }
            if ($savedUa !== '' && $currentUa !== '' && !hash_equals($savedUa, $currentUa)) {
                $this->logout();
                return false;
            }
        }

        $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'sessions';
        $exists = $this->pdo->prepare('SELECT id FROM ' . $table . ' WHERE session_token = :session_token LIMIT 1');
        $exists->execute(['session_token' => (string) $auth['token']]);
        if ($exists->fetchColumn() === false) {
            $this->logout();
            return false;
        }

        $_SESSION[self::AUTH_KEY]['last_activity'] = time();
        $touch = $this->pdo->prepare('UPDATE ' . $table . ' SET last_activity_at = CURRENT_TIMESTAMP WHERE session_token = :session_token');
        $touch->execute(['session_token' => (string) $auth['token']]);

        return true;
    }

    public function userId(): ?int
    {
        $this->start();
        $auth = $_SESSION[self::AUTH_KEY] ?? null;

        if (!is_array($auth) || !isset($auth['user_id'])) {
            return null;
        }

        return (int) $auth['user_id'];
    }

    public function markReauthenticated(): void
    {
        $this->start();
        $_SESSION[self::REAUTH_KEY] = time();
    }

    public function lastReauthAt(): ?int
    {
        $this->start();
        $value = $_SESSION[self::REAUTH_KEY] ?? null;

        return is_int($value) ? $value : null;
    }

    public function logout(): void
    {
        $this->start();
        $auth = $_SESSION[self::AUTH_KEY] ?? null;

        if (is_array($auth) && isset($auth['token'])) {
            $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'sessions';
            $stmt = $this->pdo->prepare('DELETE FROM ' . $table . ' WHERE session_token = :session_token');
            $stmt->execute(['session_token' => (string) $auth['token']]);
        }

        $_SESSION = [];
        session_destroy();
    }
}
