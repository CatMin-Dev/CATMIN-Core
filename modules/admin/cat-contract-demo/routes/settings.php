<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\Response;

return [
    [
        'method'  => 'GET',
        'path'    => '/settings/contract-demo',
        'name'    => 'settings.example.index',
        'handler' => static function (Request $request): Response {
            // Ensure user is authenticated first
            $authController = new \Admin\controllers\AuthController();
            if (!$authController->requiresAuth()) {
                return Response::html('', 302, ['Location' => $authController->adminBasePath() . '/login']);
            }

            // Then check permissions
            if (!auth_can('example.settings')) {
                return Response::html('Accès non autorisé.', 403);
            }

            $controller  = new \Admin\controllers\AuthController();
            $adminBase   = $controller->adminBasePath();
            $user        = $controller->currentUser() ?? [];

            $pageTitle       = 'Paramètres · Contract demo';
            $pageDescription = 'Réglages du module exemple CATMIN.';
            $activeNav       = 'settings';
            $breadcrumbs     = [
                ['label' => 'Admin', 'href' => $adminBase . '/'],
                ['label' => 'Paramètres', 'href' => $adminBase . '/settings/general'],
                ['label' => 'Contract demo'],
            ];
            $pageActions = [];

            ob_start();
            require __DIR__ . '/../views/settings/index.php';
            $content = (string) ob_get_clean();

            ob_start();
            require CATMIN_ADMIN . '/views/layouts/admin.php';
            return Response::html((string) ob_get_clean());
        },
    ],
];
