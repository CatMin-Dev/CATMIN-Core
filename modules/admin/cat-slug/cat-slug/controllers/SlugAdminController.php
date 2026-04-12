<?php

declare(strict_types=1);

namespace Modules\CatSlug\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatSlug\repositories\SlugRegistryRepository;
use Modules\CatSlug\services\SlugCollisionService;
use Modules\CatSlug\services\SlugGeneratorService;
use Modules\CatSlug\services\SlugNormalizerService;
use Modules\CatSlug\services\SlugRegistryService;
use Modules\CatSlug\services\SlugValidationService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class SlugAdminController
{
    private SlugRegistryService $service;
    private array $tr;

    public function __construct()
    {
        $normalizer = new SlugNormalizerService();
        $generator = new SlugGeneratorService($normalizer);
        $collision = new SlugCollisionService();
        $validator = new SlugValidationService();
        $repo = new SlugRegistryRepository();
        $this->service = new SlugRegistryService($repo, $generator, $collision, $validator);
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard('slug.read')) !== null) {
            return $guard;
        }

        return $this->render('index', [
            'rows' => $this->service->recent(100),
            'message' => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'suggested' => trim((string) $request->input('suggested', '')),
            'tr' => $this->tr,
        ]);
    }

    public function generate(Request $request): Response
    {
        if (($guard = $this->guard('slug.write')) !== null) {
            return $guard;
        }

        $entityType = trim((string) $request->input('entity_type', ''));
        $entityId = (int) $request->input('entity_id', 0);
        $sourceText = trim((string) $request->input('source_text', ''));
        $scopeKey = trim((string) $request->input('scope_key', 'global'));
        $manualSlug = trim((string) $request->input('manual_slug', ''));

        $result = $this->service->generateAndReserve($entityType, $entityId, $sourceText, $scopeKey, $manualSlug === '' ? null : $manualSlug);
        return $this->redirect('/slug-bridge', [
            'msg' => (string) ($result['message'] ?? 'Execution terminee'),
            'mt' => ($result['ok'] ?? false) ? 'success' : 'danger',
            'suggested' => (string) ($result['slug'] ?? ''),
        ]);
    }

    public function validate(Request $request): Response
    {
        if (($guard = $this->guard('slug.read')) !== null) {
            return $guard;
        }

        $slug = trim((string) $request->input('slug', ''));
        $scopeKey = trim((string) $request->input('scope_key', 'global'));
        $result = $this->service->validateInScope($slug, $scopeKey, null);

        return $this->redirect('/slug-bridge', [
            'msg' => ($result['available'] ?? false)
                ? (string) ($this->tr['slug_available'] ?? 'Slug disponible')
                : ((string) ($this->tr['slug_unavailable'] ?? 'Slug indisponible') . ': ' . (string) ($result['reason'] ?? '')),
            'mt' => ($result['available'] ?? false) ? 'success' : 'warning',
        ]);
    }

    private function loadTranslations(): array
    {
        $locale = strtolower(trim((string) config('app.locale', 'fr')));
        $allowed = ['fr', 'en'];
        if (!in_array($locale, $allowed, true)) {
            $locale = 'fr';
        }

        $base = CATMIN_MODULES . '/admin/cat-slug/lang/';
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
        $viewPath = CATMIN_MODULES . '/admin/cat-slug/views/' . $template . '.php';
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
            return (new \CoreErrorDispatcher())->response(403, ['title' => 'Acces refuse', 'message' => 'Permission requise: ' . $permission]);
        }
        return null;
    }
}
