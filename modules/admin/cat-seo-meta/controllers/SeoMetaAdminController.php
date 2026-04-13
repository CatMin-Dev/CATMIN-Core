<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatSeoMeta\repositories\SeoMetaRepository;
use Modules\CatSeoMeta\services\SeoAuditService;
use Modules\CatSeoMeta\services\SeoKeywordSuggestService;
use Modules\CatSeoMeta\services\SeoMetaService;
use Modules\CatSeoMeta\services\SeoPreviewService;
use Modules\CatSeoMeta\services\SeoScoreService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class SeoMetaAdminController
{
    private SeoMetaService $service;
    private array $tr;

    public function __construct()
    {
        $repo = new SeoMetaRepository();
        $score = new SeoScoreService();
        $audit = new SeoAuditService($score);
        $preview = new SeoPreviewService();
        $keywords = new SeoKeywordSuggestService();
        $this->service = new SeoMetaService($repo, $score, $audit, $preview, $keywords);
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard('seo.read')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', 'page')));
        $entityId = max(0, (int) $request->input('entity_id', 0));

        $record = $entityId > 0 ? $this->service->get($entityType, $entityId) : [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'robots_index' => 1,
            'robots_follow' => 1,
        ];

        return $this->render('index', [
            'dashboard' => $this->service->dashboardState(),
            'record' => $record,
            'preview' => $this->service->preview($record),
            'message' => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'auditSummary' => trim((string) $request->input('audit', '')),
            'tr' => $this->tr,
        ]);
    }

    public function save(Request $request): Response
    {
        if (($guard = $this->guard('seo.write')) !== null) {
            return $guard;
        }

        $payload = $this->extractPayload($request);
        $state = $this->service->save($payload);

        return $this->redirect('/modules/seo-meta', [
            'entity_type' => (string) ($payload['entity_type'] ?? 'page'),
            'entity_id' => (int) ($payload['entity_id'] ?? 0),
            'msg' => (string) ($state['message'] ?? 'Saved'),
            'mt' => (bool) ($state['ok'] ?? false) ? 'success' : 'danger',
            'audit' => (bool) ($state['ok'] ?? false)
                ? ('SEO score: ' . (int) ($state['score'] ?? 0) . '/100')
                : '',
        ]);
    }

    public function audit(Request $request): Response
    {
        if (($guard = $this->guard('seo.audit')) !== null) {
            return $guard;
        }

        $payload = $this->extractPayload($request);
        $state = $this->service->auditOnly($payload);

        return $this->redirect('/modules/seo-meta', [
            'entity_type' => (string) ($payload['entity_type'] ?? 'page'),
            'entity_id' => (int) ($payload['entity_id'] ?? 0),
            'msg' => (string) ($state['summary'] ?? 'Audit done'),
            'mt' => 'info',
            'audit' => 'SEO score: ' . (int) ($state['score'] ?? 0) . '/100',
        ]);
    }

    private function extractPayload(Request $request): array
    {
        return [
            'entity_type' => trim((string) $request->input('entity_type', '')),
            'entity_id' => (int) $request->input('entity_id', 0),
            'seo_title' => trim((string) $request->input('seo_title', '')),
            'meta_description' => trim((string) $request->input('meta_description', '')),
            'canonical_url' => trim((string) $request->input('canonical_url', '')),
            'robots_index' => $request->input('robots_index', null) !== null ? 1 : 0,
            'robots_follow' => $request->input('robots_follow', null) !== null ? 1 : 0,
            'og_title' => trim((string) $request->input('og_title', '')),
            'og_description' => trim((string) $request->input('og_description', '')),
            'og_image_media_id' => (int) $request->input('og_image_media_id', 0),
            'focus_keyword' => trim((string) $request->input('focus_keyword', '')),
        ];
    }

    private function loadTranslations(): array
    {
        $locale = function_exists('catmin_locale')
            ? strtolower(trim(catmin_locale()))
            : strtolower(trim((string) config('app.locale', 'fr')));
        if (!in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $base = CATMIN_MODULES . '/admin/cat-seo-meta/lang/';
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
        $viewPath = CATMIN_MODULES . '/admin/cat-seo-meta/views/' . $template . '.php';
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
