<?php

declare(strict_types=1);

namespace Core\auth;

use Core\config\Config;
use PDO;

final class AdminAuthenticator
{
    private SessionManager $sessions;
    private LockoutManager $lockout;
    private PasswordHasher $hasher;

    public function __construct(private readonly PDO $pdo)
    {
        $this->sessions = new SessionManager($this->pdo);
        $this->lockout = new LockoutManager($this->pdo);
        $this->hasher = new PasswordHasher();
    }

    public function sessions(): SessionManager
    {
        return $this->sessions;
    }

    public function attempt(string $identifier, string $password, string $ipAddress, string $userAgent): array
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return ['ok' => false, 'message' => 'Identifiants invalides.'];
        }

        if ($this->lockout->isLocked($identifier, $ipAddress)) {
            $this->logSecurityEvent(null, 'auth.lockout.active', 'warning', ['identifier' => $identifier, 'ip' => $ipAddress]);
            return ['ok' => false, 'message' => 'Compte temporairement verrouille.'];
        }

        $user = $this->findUser($identifier);
        if (!is_array($user) || !$this->hasher->verify($password, (string) ($user['password_hash'] ?? ''))) {
            $this->lockout->recordAttempt($identifier, $ipAddress, false);
            $this->logSecurityEvent(null, 'auth.login.failed', 'warning', ['identifier' => $identifier, 'ip' => $ipAddress]);
            return ['ok' => false, 'message' => 'Identifiants invalides.'];
        }

        if (!(bool) ($user['is_active'] ?? false)) {
            $this->lockout->recordAttempt($identifier, $ipAddress, false);
            $this->logSecurityEvent((int) $user['id'], 'auth.login.inactive', 'warning', ['identifier' => $identifier]);
            return ['ok' => false, 'message' => 'Compte desactive.'];
        }

        $this->lockout->recordAttempt($identifier, $ipAddress, true);
        $this->sessions->login((int) $user['id'], $ipAddress, $userAgent);

        $usersTable = (string) Config::get('database.prefixes.admin', 'admin_') . 'users';
        $updateStmt = $this->pdo->prepare('UPDATE ' . $usersTable . ' SET last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->execute(['id' => (int) $user['id']]);

        $this->logSecurityEvent((int) $user['id'], 'auth.login.success', 'info', ['ip' => $ipAddress]);

        return ['ok' => true, 'message' => 'Connexion reussie.', 'user' => $user];
    }

    public function currentUser(): ?array
    {
        if (!$this->sessions->isAuthenticated()) {
            return null;
        }

        $userId = $this->sessions->userId();
        if ($userId === null) {
            return null;
        }

        $usersTable = (string) Config::get('database.prefixes.admin', 'admin_') . 'users';
        $stmt = $this->pdo->prepare('SELECT id, role_id, username, email, is_active, last_login_at FROM ' . $usersTable . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function logout(): void
    {
        $userId = $this->sessions->userId();
        $this->sessions->logout();
        $this->logSecurityEvent($userId, 'auth.logout', 'info', []);
    }

    public function verifyReauth(string $password): bool
    {
        $user = $this->currentUser();
        if (!is_array($user)) {
            return false;
        }

        $usersTable = (string) Config::get('database.prefixes.admin', 'admin_') . 'users';
        $stmt = $this->pdo->prepare('SELECT password_hash FROM ' . $usersTable . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $user['id']]);
        $hash = (string) ($stmt->fetchColumn() ?: '');

        return $hash !== '' && $this->hasher->verify($password, $hash);
    }

    private function findUser(string $identifier): ?array
    {
        $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'users';
        $stmt = $this->pdo->prepare(
            'SELECT id, role_id, username, email, password_hash, is_active FROM ' . $table . ' WHERE email = :identifier OR username = :identifier LIMIT 1'
        );
        $stmt->execute(['identifier' => $identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    private function logSecurityEvent(?int $userId, string $eventType, string $severity, array $payload): void
    {
        $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'security_events';
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . $table . ' (user_id, event_type, severity, payload, ip_address, created_at) VALUES (:user_id, :event_type, :severity, :payload, :ip_address, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'event_type' => substr($eventType, 0, 120),
            'severity' => substr($severity, 0, 40),
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => substr((string) ($payload['ip'] ?? ''), 0, 64),
        ]);
    }
}
