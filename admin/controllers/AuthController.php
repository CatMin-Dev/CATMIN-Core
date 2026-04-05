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
