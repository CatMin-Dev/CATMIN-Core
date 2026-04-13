<?php

declare(strict_types=1);

namespace Modules\CatCategories\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatCategories\repositories\CategoriesRepository;
use Modules\CatCategories\services\CategoriesService;
use Modules\CatCategories\services\CategoryLinkService;
use Modules\CatCategories\services\CategorySelectorService;
use Modules\CatCategories\services\CategoryTreeService;
use Modules\CatCategories\services\CategoryUsageService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class CategoriesAdminController
{
    private CategoriesService $service;
    private array $tr;

    public function __construct()
    {
        $repo = new CategoriesRepository();
        $this->service = new CategoriesService(
            $repo,
            new CategoryTreeService(),
            new CategoryLinkService($repo),
            new CategoryUsageService($repo),
            new CategorySelectorService()
        );
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard('categories.read')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', 'page')));
        $entityId = max(0, (int) $request->input('entity_id', 0));
        $state = $this->service->dashboard();
        $selectedIds = $entityId > 0 ? $this->service->entityCategoryIds($entityType, $entityId) : [];

        return $this->render('index', [
            'dashboard' => $state,
            'selectedIds' => $selectedIds,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'message' => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'tr' => $this->tr,
        ]);
    }

    public function create(Request $request): Response
    {
        if (($guard = $this->guard('categories.write')) !== null) {
            return $guard;
        }

        $name = trim((string) $request->input('name', ''));
        $parentId = (int) $request->input('parent_id', 0);
        $sortOrder = (int) $request->input('sort_order', 0);
        $state = $this->service->createCategory($name, $parentId > 0 ? $parentId : null, $sortOrder);

        return $this->redirect('/modules/categories-bridge', [
            'msg' => (string) ($state['message'] ?? 'Operation terminee'),
            'mt' => (bool) ($state['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function sync(Request $request): Response
    {
        if (($guard = $this->guard('categories.write')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', '')));
        $entityId = (int) $request->input('entity_id', 0);
        $selected = $request->input('category_ids', []);
        $state = $this->service->syncEntityCategories($entityType, $entityId, $selected);

        return $this->redirect('/modules/categories-bridge', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'msg' => (string) ($state['message'] ?? 'Sync done'),
            'mt' => (bool) ($state['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function tree(Request $request): Response
    {
        if (($guard = $this->guard('categories.read')) !== null) {
            return $guard;
        }
        $state = $this->service->dashboard();
        return Response::json(['ok' => true, 'tree' => (array) ($state['tree'] ?? [])]);
    }

    private function loadTranslations(): array
    {
        $locale = strtolower(trim((string) config('app.locale', 'fr')));
        if (!in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $base = CATMIN_MODULES . '/admin/cat-categories/lang/';
        $selected = $base . $locale . '/module.php';
        $fallback = $base . 'en/module.php';

        $rows = [];
        if (is_file($selected)) {
            $loaded = require $selected;
            if (is_array($loaded)) {
                $rows = $loaded;
            }
        }
        if ($rows === [] && is_file($fallback)) {
            $loaded = require $fallback;
            if (is_array($loaded)) {
                $rows = $loaded;
            }
        }
        return $rows;
    }

    private function render(string $template, array $data): Response
    {
        $adminBase = $this->adminBase();
        extract($data, EXTR_SKIP);
        $viewPath = CATMIN_MODULES . '/admin/cat-categories/views/' . $template . '.php';
        ob_start();
        require $viewPath;
        return Response::html((string) ob_get_clean());
    }

    private function redirect(string $path, array $query = []): Response
    {
        $base = $this->adminBase();
        $qs = $query !== [] ? ('?' . http_build_query($query)) : '';
        return Response::html('', 302, ['Location' => $base . $path . $qs]);
    }

    private function adminBase(): string
    {
        $path = trim((string) config('security.admin_path', 'admin'), '/');
        if ($path === '' || str_contains($path, '..')) {
            $path = 'admin';
        }
        return '/' . $path;
    }

    private function guard(string $permission): ?Response
    {
        $pdo = (new ConnectionManager())->connection();
        $sessions = new SessionManager($pdo);
        $uid = $sessions->userId();
        if ($uid === null) {
            return Response::html('', 302, ['Location' => $this->adminBase() . '/login']);
        }
        if (function_exists('auth_can') && !auth_can($permission)) {
            return (new \CoreErrorDispatcher())->response(403, ['title' => 'Access denied', 'message' => 'Required permission: ' . $permission]);
        }
        return null;
    }
}
