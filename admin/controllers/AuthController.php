<?php

declare(strict_types=1);

namespace Admin\controllers;

use Core\auth\AdminAuthenticator;
use Core\auth\ReAuthManager;
use Core\config\Config;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Core\http\View;
require_once CATMIN_CORE . '/error-dispatcher.php';

final class AuthController
{
    private AdminAuthenticator $auth;
    private ReAuthManager $reauth;

    public function __construct()
    {
        $pdo = (new ConnectionManager())->connection();
        $this->auth = new AdminAuthenticator($pdo);
        $this->reauth = new ReAuthManager($this->auth->sessions());
    }

    public function adminBasePath(): string
    {
        $path = '/' . trim((string) Config::get('security.admin_path', 'admin'), '/');

        return $path === '//' ? '/admin' : $path;
    }

    public function showLogin(?string $error = null): Response
    {
        return View::make('auth.login', ['error' => $error, 'adminBase' => $this->adminBasePath()], 'admin');
    }

    public function login(Request $request): Response
    {
        $identifier = (string) $request->input('identifier', '');
        $password = (string) $request->input('password', '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

        $result = $this->auth->attempt($identifier, $password, $ip, $ua);
        if (!$result['ok']) {
            $status = (int) ($result['status'] ?? 401);
            if ($status === 429) {
                return (new \CoreErrorDispatcher())->response(429, [
                    'message' => 'Trop de tentatives de connexion. Réessaie plus tard.',
                    'admin_login' => $this->adminBasePath() . '/login',
                ]);
            }
            return $this->showLogin((string) ($result['message'] ?? 'Erreur de connexion.'));
        }

        return Response::html('', 302, ['Location' => $this->adminBasePath() . '/']);
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return Response::html('', 302, ['Location' => $this->adminBasePath() . '/login']);
    }

    public function showReauth(?string $error = null): Response
    {
        return View::make('auth.reauth', ['error' => $error, 'adminBase' => $this->adminBasePath()], 'admin');
    }

    public function reauth(Request $request): Response
    {
        $password = (string) $request->input('password', '');

        if (!$this->auth->verifyReauth($password)) {
            return $this->showReauth('Mot de passe invalide.');
        }

        $this->reauth->markValidated();

        return Response::html('', 302, ['Location' => $this->adminBasePath() . '/']);
    }

    public function showPasswordRequest(?string $message = null): Response
    {
        return View::make('auth.password-request', [
            'message' => $message ?? 'Recuperation par email desactivee pour le coeur admin.',
            'adminBase' => $this->adminBasePath(),
        ], 'admin');
    }

    public function passwordRequest(Request $request): Response
    {
        return $this->showPasswordRequest('Recuperation par email desactivee pour le coeur admin.');
    }

    public function showPasswordReset(?string $error = null, ?string $message = null): Response
    {
        return View::make('auth.password-reset', [
            'error' => $error,
            'message' => $message,
            'adminBase' => $this->adminBasePath(),
        ], 'admin');
    }

    public function passwordReset(Request $request): Response
    {
        return $this->showPasswordReset('Reset standard indisponible. Utilise le changement de mot de passe en session.');
    }

    public function showLocked(): Response
    {
        return View::make('auth.locked', ['adminBase' => $this->adminBasePath()], 'admin');
    }

    public function showPasswordChange(?string $error = null, ?string $message = null): Response
    {
        return View::make('auth.password-reset', [
            'error' => $error,
            'message' => $message,
            'adminBase' => $this->adminBasePath(),
            'mode' => 'change',
        ], 'admin');
    }

    public function passwordChange(Request $request): Response
    {
        $current = (string) $request->input('current_password', '');
        $password = (string) $request->input('password', '');
        $passwordConfirm = (string) $request->input('password_confirm', '');

        if ($password === '' || $password !== $passwordConfirm) {
            return $this->showPasswordChange('Les mots de passe ne correspondent pas.');
        }

        $user = $this->currentUser();
        if (!is_array($user) || !isset($user['id'])) {
            return Response::html('', 302, ['Location' => $this->adminBasePath() . '/login']);
        }

        $result = $this->auth->changePassword((int) $user['id'], $current, $password);
        if (!((bool) ($result['ok'] ?? false))) {
            return $this->showPasswordChange((string) ($result['message'] ?? 'Echec changement mot de passe.'));
        }

        $this->reauth->markValidated();
        return $this->showPasswordChange(null, 'Mot de passe mis a jour.');
    }

    public function requiresAuth(): bool
    {
        return $this->auth->sessions()->isAuthenticated();
    }

    public function requiresRecentReauth(): bool
    {
        return $this->reauth->isRecent();
    }

    public function currentUser(): ?array
    {
        return $this->auth->currentUser();
    }
}
