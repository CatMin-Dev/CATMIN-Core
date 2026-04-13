<?php

declare(strict_types=1);

namespace Modules\CatAuthors\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatAuthors\repositories\AuthorRepository;
use Modules\CatAuthors\services\AuthorLinkService;
use Modules\CatAuthors\services\AuthorProfileService;
use Modules\CatAuthors\services\AuthorSelectorService;
use Modules\CatAuthors\services\AuthorValidationService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class AuthorAdminController
{
    private AuthorProfileService $profileService;
    private AuthorLinkService $linkService;
    private AuthorSelectorService $selectorService;
    private array $tr;

    public function __construct()
    {
        $repo = new AuthorRepository();
        $validator = new AuthorValidationService($repo);
        $this->profileService = new AuthorProfileService($repo, $validator);
        $this->linkService = new AuthorLinkService($repo);
        $this->selectorService = new AuthorSelectorService($repo);
        $this->tr = $this->loadTranslations();
    }

    public function index(Request $request): Response
    {
        if (($guard = $this->guard(['authors.read', 'author.read', 'core.modules.manage'])) !== null) {
            return $guard;
        }

        return $this->render('index', [
            'dashboard'   => $this->profileService->dashboard(),
            'message'     => trim((string) $request->input('msg', '')),
            'messageType' => trim((string) $request->input('mt', 'info')),
            'tr'          => $this->tr,
        ]);
    }

    public function createProfile(Request $request): Response
    {
        if (($guard = $this->guard(['authors.write', 'author.write', 'core.modules.manage'])) !== null) {
            return $guard;
        }

        [$status, $result] = $this->profileService->create($request->post());
        return $this->redirect('/modules/author-bridge', [
            'msg' => $status === 'ok' ? (string) ($this->tr['msg_profile_created'] ?? 'Profile created successfully.') : (string) $result,
            'mt'  => $status === 'ok' ? 'success' : 'danger',
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        if (($guard = $this->guard(['authors.write', 'author.write', 'core.modules.manage'])) !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);
        [$status, $result] = $this->profileService->update($id, $request->post());
        return $this->redirect('/modules/author-bridge', [
            'msg' => $status === 'ok' ? (string) ($this->tr['msg_profile_updated'] ?? 'Profile updated.') : (string) $result,
            'mt'  => $status === 'ok' ? 'success' : 'danger',
        ]);
    }

    public function deleteProfile(Request $request): Response
    {
        if (($guard = $this->guard(['authors.delete', 'author.delete', 'core.modules.manage'])) !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);
        if ($id > 0) {
            $this->profileService->delete($id);
        }
        return $this->redirect('/modules/author-bridge', [
            'msg' => (string) ($this->tr['msg_profile_deleted'] ?? 'Profile deleted.'),
            'mt'  => 'success',
        ]);
    }

    public function syncEntity(Request $request): Response
    {
        if (($guard = $this->guard(['authors.write', 'author.write', 'core.modules.manage'])) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', '')));
        $entityId   = (int) $request->input('entity_id', 0);
        $profileId  = (int) $request->input('author_profile_id', 0);

        if ($entityType !== '' && $entityId > 0) {
            $this->linkService->syncEntity($entityType, $entityId, $profileId > 0 ? $profileId : null);
        }

        return Response::json(['ok' => true]);
    }

    public function panel(Request $request): Response
    {
        if (($guard = $this->guard(['authors.read', 'author.read', 'core.modules.manage'])) !== null) {
            return $guard;
        }

        $entityType = strtolower(trim((string) $request->input('entity_type', '')));
        $entityId   = (int) $request->input('entity_id', 0);
        $selectedId = $entityId > 0
            ? $this->linkService->entityAuthorId($entityType, $entityId)
            : null;

        return $this->render('embedded_panel', [
            'profiles'   => $this->selectorService->listForSelect(),
            'selectedId' => $selectedId,
            'entityType' => $entityType,
            'entityId'   => $entityId,
            'tr'         => $this->tr,
        ]);
    }

    private function loadTranslations(): array
    {
        $locale = function_exists('catmin_locale')
            ? strtolower(trim(catmin_locale()))
            : strtolower(trim((string) config('app.locale', 'fr')));
        if (!in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }
        $base     = CATMIN_MODULES . '/admin/cat-authors/lang/';
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
        $data['adminBase'] = $data['adminBase'] ?? $this->adminBase();
        extract($data, EXTR_SKIP);
        $viewPath = CATMIN_MODULES . '/admin/cat-authors/views/' . $template . '.php';
        ob_start();
        require $viewPath;
        return Response::html((string) ob_get_clean());
    }

    private function redirect(string $path, array $query = []): Response
    {
        $base = $this->adminBase();
        $qs   = $query !== [] ? ('?' . http_build_query($query)) : '';
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

    private function guard(string|array $permission): ?Response
    {
        $pdo      = (new ConnectionManager())->connection();
        $sessions = new SessionManager($pdo);
        $uid      = $sessions->userId();
        if ($uid === null) {
            return Response::html('', 302, ['Location' => $this->adminBase() . '/login']);
        }
        $required = is_array($permission) ? array_values($permission) : [$permission];
        if (function_exists('auth_can')) {
            $allowed = false;
            foreach ($required as $perm) {
                if (is_string($perm) && $perm !== '' && auth_can($perm)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return (new \CoreErrorDispatcher())->response(403, ['title' => 'Access denied', 'message' => 'Required permission: ' . implode(' OR ', array_map('strval', $required))]);
            }
        }
        return null;
    }
}
