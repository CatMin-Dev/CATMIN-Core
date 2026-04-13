<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatMediaLink\repositories\MediaLinkRepository;
use Modules\CatMediaLink\services\FeaturedMediaService;
use Modules\CatMediaLink\services\MediaGalleryService;
use Modules\CatMediaLink\services\MediaLinkService;
use Modules\CatMediaLink\services\MediaLinkValidationService;
use Modules\CatMediaLink\services\MediaUsageService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class MediaLinkAdminController
{
    private MediaLinkService $service;
    private array $tr;

    public function __construct()
    {
        $repository = new MediaLinkRepository();
        $this->service = new MediaLinkService(
            $repository,
            new MediaLinkValidationService(),
            new MediaGalleryService(),
            new FeaturedMediaService(),
            new MediaUsageService($repository)
        );
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard('content.media.read')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', 'page')));
        $entityId = max(0, (int) $request->input('entity_id', 0));
        $preview = $entityId > 0 ? $this->service->entityPreview($entityType, $entityId) : ['links' => [], 'featured' => null];

        return $this->render('index', [
            'state' => $this->service->dashboard(),
            'preview' => $preview,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'message' => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'tr' => $this->tr,
        ]);
    }

    public function upload(Request $request): Response
    {
        if (($guard = $this->guard('content.media.write')) !== null) {
            return $guard;
        }

        $file = $_FILES['media_file'] ?? [];
        $result = $this->service->storeUploadedAsset(
            is_array($file) ? $file : [],
            trim((string) $request->input('title', '')),
            trim((string) $request->input('alt_text', ''))
        );

        return $this->redirect('/modules/media-link', [
            'msg' => (string) ($result['message'] ?? 'Operation terminée.'),
            'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function addUrl(Request $request): Response
    {
        if (($guard = $this->guard('content.media.write')) !== null) {
            return $guard;
        }

        $result = $this->service->storeExternalAsset(
            trim((string) $request->input('url', '')),
            trim((string) $request->input('media_type', 'image')),
            trim((string) $request->input('title', '')),
            trim((string) $request->input('alt_text', ''))
        );

        return $this->redirect('/modules/media-link', [
            'msg' => (string) ($result['message'] ?? 'Operation terminée.'),
            'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function sync(Request $request): Response
    {
        if (($guard = $this->guard('content.media.manage')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', 'page')));
        $entityId = (int) $request->input('entity_id', 0);
        $result = $this->service->syncEntity(
            $entityType,
            $entityId,
            (int) $request->input('featured_media_id', 0),
            trim((string) $request->input('gallery_media_ids', '')),
            (int) $request->input('social_media_id', 0)
        );

        return $this->redirect('/modules/media-link', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'msg' => (string) ($result['message'] ?? 'Operation terminée.'),
            'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function panel(Request $request): Response
    {
        if (($guard = $this->guard('content.media.read')) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', 'page')));
        $entityId = max(0, (int) $request->input('entity_id', 0));

        return $this->render('embedded_panel', [
            'state' => $this->service->dashboard(),
            'preview' => $entityId > 0 ? $this->service->entityPreview($entityType, $entityId) : ['links' => [], 'featured' => null],
            'entityType' => $entityType,
            'entityId' => $entityId,
            'tr' => $this->tr,
        ]);
    }

    public function adminScript(Request $request): Response
    {
        if (($guard = $this->guard('content.media.read')) !== null) {
            return $guard;
        }

        $path = CATMIN_MODULES . '/admin/cat-media-link/assets/js/admin.js';
        if (!is_file($path)) {
            return Response::html('// media admin script missing', 404, ['Content-Type' => 'application/javascript; charset=UTF-8']);
        }

        return Response::html((string) file_get_contents($path), 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
    }

    private function loadTranslations(): array
    {
        $locale = function_exists('catmin_locale')
            ? strtolower(trim((string) catmin_locale()))
            : strtolower(trim((string) config('app.locale', 'fr')));
        if (!in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $base = CATMIN_MODULES . '/admin/cat-media-link/lang/';
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
        $viewPath = CATMIN_MODULES . '/admin/cat-media-link/views/' . $template . '.php';
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
