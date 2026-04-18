<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\Response;

return [
    [
        'method'  => 'GET',
        'path'    => '/contract-demo',
        'name'    => 'admin.example.dashboard',
        'handler' => static function (Request $request): Response {
            // Ensure user is authenticated first
            $authController = new \Admin\controllers\AuthController();
            if (!$authController->requiresAuth()) {
                return Response::html('', 302, ['Location' => $authController->adminBasePath() . '/login']);
            }

            // Then check permissions
            if (!auth_can('example.read')) {
                return Response::html('Accès non autorisé.', 403);
            }

            $controller  = new \Admin\controllers\AuthController();
            $adminBase   = $controller->adminBasePath();
            $user        = $controller->currentUser() ?? [];

            $pageTitle       = 'Contract Demo';
            $pageDescription = 'Module exemple CATMIN — validation du contrat CORE/MODULE.';
            $activeNav       = 'contract-demo';
            $breadcrumbs     = [
                ['label' => 'Admin', 'href' => $adminBase . '/'],
                ['label' => 'Contract Demo'],
            ];
            $pageActions = [];

            ob_start();
            require __DIR__ . '/../views/admin/pages/dashboard.php';
            $content = (string) ob_get_clean();

            ob_start();
            require CATMIN_ADMIN . '/views/layouts/admin.php';
            return Response::html((string) ob_get_clean());
        },
    ],
];
