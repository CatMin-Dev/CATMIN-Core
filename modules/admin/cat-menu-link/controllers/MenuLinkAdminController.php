<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatMenuLink\repositories\MenuLinkRepository;
use Modules\CatMenuLink\services\BreadcrumbSeedService;
use Modules\CatMenuLink\services\MenuAttachmentService;
use Modules\CatMenuLink\services\MenuLinkService;
use Modules\CatMenuLink\services\MenuLinkValidationService;
use Modules\CatMenuLink\services\MenuTreeService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class MenuLinkAdminController
{
    private MenuLinkService $service;
    private array $tr;

    public function __construct()
    {
        $repository = new MenuLinkRepository();
        $validator = new MenuLinkValidationService();
        $this->service = new MenuLinkService(
            $repository,
            new MenuAttachmentService($repository, $validator),
            new MenuTreeService(),
            new BreadcrumbSeedService()
        );
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard('content.menu.read')) !== null) {
            return $guard;
        }

        $menuKey = trim((string) $request->input('menu_key', 'main_nav'));
        return $this->render('index', [
            'state' => $this->service->dashboard($menuKey),
            'message' => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'tr' => $this->tr,
        ]);
    }

    public function attach(Request $request): Response
    {
        if (($guard = $this->guard('content.menu.write')) !== null) {
            return $guard;
        }

        $result = $this->service->attach([
            'menu_key' => (string) $request->input('menu_key', 'main_nav'),
            'entity_type' => (string) $request->input('entity_type', 'page'),
            'entity_id' => (int) $request->input('entity_id', 0),
            'parent_item_id' => (int) $request->input('parent_item_id', 0),
            'label_override' => (string) $request->input('label_override', ''),
            'target_url' => (string) $request->input('target_url', ''),
            'link_type' => (string) $request->input('link_type', 'entity_link'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_visible' => (string) $request->input('is_visible', '1') === '1',
        ]);

        return $this->redirect('/modules/menu-link', [
            'menu_key' => (string) $request->input('menu_key', 'main_nav'),
            'msg' => (string) ($result['message'] ?? 'Operation terminee.'),
            'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function reorder(Request $request): Response
    {
        if (($guard = $this->guard('content.menu.manage')) !== null) {
            return $guard;
        }

        $menuKey = trim((string) $request->input('menu_key', 'main_nav'));
        $raw = trim((string) $request->input('order_json', '[]'));
        $rows = json_decode($raw, true);
        $rows = is_array($rows) ? $rows : [];
        $result = $this->service->reorder($menuKey, $rows);

        return $this->redirect('/modules/menu-link', [
            'menu_key' => $menuKey,
            'msg' => (string) ($result['message'] ?? 'Operation terminee.'),
            'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function delete(Request $request): Response
    {
        if (($guard = $this->guard('content.menu.manage')) !== null) {
            return $guard;
        }

        $menuKey = trim((string) $request->input('menu_key', 'main_nav'));
        $result = $this->service->delete((int) $request->input('id', 0));

        return $this->redirect('/modules/menu-link', [
            'menu_key' => $menuKey,
            'msg' => (string) ($result['message'] ?? 'Operation terminee.'),
            'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
        ]);
    }

    public function panel(Request $request): Response
    {
        if (($guard = $this->guard('content.menu.read')) !== null) {
            return $guard;
        }

        $menuKey = trim((string) $request->input('menu_key', 'main_nav'));
        return $this->render('embedded_panel', [
            'state' => $this->service->dashboard($menuKey),
            'tr' => $this->tr,
        ]);
    }

    public function adminScript(Request $request): Response
    {
        if (($guard = $this->guard('content.menu.read')) !== null) {
            return $guard;
        }

        $path = CATMIN_MODULES . '/admin/cat-menu-link/assets/js/admin.js';
        if (!is_file($path)) {
            return Response::html('// menu admin script missing', 404, ['Content-Type' => 'application/javascript; charset=UTF-8']);
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

        $base = CATMIN_MODULES . '/admin/cat-menu-link/lang/';
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
        $viewPath = CATMIN_MODULES . '/admin/cat-menu-link/views/' . $template . '.php';
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
