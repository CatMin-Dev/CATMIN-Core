<?php

declare(strict_types=1);

namespace Modules\CatTags\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatTags\repositories\TagsRepository;
use Modules\CatTags\services\TagAutocompleteService;
use Modules\CatTags\services\TagDisplayService;
use Modules\CatTags\services\TagLinkService;
use Modules\CatTags\services\TagUsageService;
use Modules\CatTags\services\TagsService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class TagsAdminController
{
    private TagsService $service;
    private array $tr;

    public function __construct()
    {
        $repo = new TagsRepository();
        $this->service = new TagsService(
            $repo,
            new TagAutocompleteService($repo),
            new TagLinkService($repo),
            new TagUsageService($repo),
            new TagDisplayService()
        );
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard('tags.read')) !== null) {
            return $guard;
        }

        $q = trim((string) $request->input('q', ''));
        $entityType = strtolower(trim((string) $request->input('entity_type', 'page')));
        $entityId = max(0, (int) $request->input('entity_id', 0));
        $tagsCsv = $entityId > 0 ? $this->service->entityTagsCsv($entityType, $entityId) : '';

        return $this->render('index', [
            'dashboard' => $this->service->dashboard($q),
            'tagsCsv' => $tagsCsv,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'message' => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'tr' => $this->tr,
        ]);
    }

    public function sync(Request $request): Response
    {
        if (($guard = $this->guard('tags.write')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', '')));
        $entityId = (int) $request->input('entity_id', 0);
        $tagsCsv = trim((string) $request->input('tags_csv', ''));

        $state = $this->service->syncEntity($entityType, $entityId, $tagsCsv);

        return $this->redirect('/modules/tags-bridge', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'q' => '',
            'msg' => (string) ($state['message'] ?? 'Sync done'),
            'mt' => (bool) ($state['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function suggest(Request $request): Response
    {
        if (($guard = $this->guard('tags.read')) !== null) {
            return $guard;
        }

        $q = trim((string) $request->input('q', ''));
        $items = $this->service->suggest($q);
        return Response::json(['ok' => true, 'items' => $items]);
    }

    private function loadTranslations(): array
    {
        $locale = strtolower(trim((string) config('app.locale', 'fr')));
        if (!in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $base = CATMIN_MODULES . '/admin/cat-tags/lang/';
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
        $viewPath = CATMIN_MODULES . '/admin/cat-tags/views/' . $template . '.php';
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
