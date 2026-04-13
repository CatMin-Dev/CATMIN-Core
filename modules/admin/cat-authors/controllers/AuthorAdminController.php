<?php

declare(strict_types=1);

namespace Modules\CatAuthors\controllers;

use Core\auth\SessionManager;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Modules\CatAuthors\repositories\AuthorRepository;
use Modules\CatAuthors\services\AuthorDisplayService;
use Modules\CatAuthors\services\AuthorLinkService;
use Modules\CatAuthors\services\AuthorProfileService;
use Modules\CatAuthors\services\AuthorRoleRegistryService;
use Modules\CatAuthors\services\AuthorSelectorService;
use Modules\CatAuthors\services\AuthorValidationService;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class AuthorAdminController
{
    private AuthorProfileService $profileService;
    private AuthorLinkService $linkService;
    private AuthorSelectorService $selectorService;
    private AuthorRoleRegistryService $roleRegistry;
    private AuthorDisplayService $displayService;
    private array $tr;

    public function __construct()
    {
        $repo = new AuthorRepository();
        $validator = new AuthorValidationService($repo);
        $this->profileService = new AuthorProfileService($repo, $validator);
        $this->linkService = new AuthorLinkService($repo);
        $this->selectorService = new AuthorSelectorService($repo);
        $this->roleRegistry = new AuthorRoleRegistryService($repo);
        $this->displayService = new AuthorDisplayService();
        $this->tr = $this->loadTranslations();
    }

    // -------------------------------------------------------------------------
    // Main page (profiles + roles tabs)
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        if (($guard = $this->guard(['authors.read', 'author.read'])) !== null) {
            return $guard;
        }

        $tab = trim((string) $request->input('tab', 'profiles'));
        $dashboard = $this->profileService->dashboard();
        $rolesWithFlag = $this->roleRegistry->allRolesWithFlag();

        return $this->render('index', [
            'tab'          => in_array($tab, ['profiles', 'roles'], true) ? $tab : 'profiles',
            'dashboard'    => $dashboard,
            'rolesWithFlag'=> $rolesWithFlag,
            'message'      => trim((string) $request->input('msg', '')),
            'messageType'  => trim((string) $request->input('mt', 'info')),
            'tr'           => $this->tr,
        ]);
    }

    // -------------------------------------------------------------------------
    // Profile CRUD
    // -------------------------------------------------------------------------

    public function createProfile(Request $request): Response
    {
        if (($guard = $this->guard(['authors.write', 'author.write'])) !== null) {
            return $guard;
        }

        [$status, $result] = $this->profileService->create($request->all());
        return $this->redirect('/modules/author-bridge', [
            'tab' => 'profiles',
            'msg' => $status === 'ok' ? 'Profil créé avec succès.' : (string) $result,
            'mt'  => $status === 'ok' ? 'success' : 'danger',
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        if (($guard = $this->guard(['authors.write', 'author.write'])) !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);
        [$status, $result] = $this->profileService->update($id, $request->all());
        return $this->redirect('/modules/author-bridge', [
            'tab' => 'profiles',
            'msg' => $status === 'ok' ? 'Profil mis à jour.' : (string) $result,
            'mt'  => $status === 'ok' ? 'success' : 'danger',
        ]);
    }

    public function deleteProfile(Request $request): Response
    {
        if (($guard = $this->guard(['authors.delete', 'author.delete'])) !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);
        if ($id > 0) {
            $this->profileService->delete($id);
        }
        return $this->redirect('/modules/author-bridge', [
            'tab' => 'profiles',
            'msg' => 'Profil supprimé.',
            'mt'  => 'success',
        ]);
    }

    // -------------------------------------------------------------------------
    // Sync entity author
    // -------------------------------------------------------------------------

    public function syncEntity(Request $request): Response
    {
        if (($guard = $this->guard(['authors.write', 'author.write'])) !== null) {
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

    // -------------------------------------------------------------------------
    // Role registry
    // -------------------------------------------------------------------------

    public function saveRoleRegistry(Request $request): Response
    {
        if (($guard = $this->guard(['authors.configure', 'author.configure'])) !== null) {
            return $guard;
        }

        $roleIds = $request->input('role_ids', []);
        if (!is_array($roleIds)) {
            $roleIds = [];
        }
        $notes = $request->input('role_notes', []);
        if (!is_array($notes)) {
            $notes = [];
        }
        $this->roleRegistry->saveRegistry($roleIds, $notes);

        return $this->redirect('/modules/author-bridge', [
            'tab' => 'roles',
            'msg' => 'Registre des rôles auteurs mis à jour.',
            'mt'  => 'success',
        ]);
    }

    // -------------------------------------------------------------------------
    // Embedded panel (GET — returns HTML fragment for module editors)
    // -------------------------------------------------------------------------

    public function panel(Request $request): Response
    {
        if (($guard = $this->guard(['authors.read', 'author.read'])) !== null) {
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function loadTranslations(): array
    {
        $locale = strtolower(trim((string) config('app.locale', 'fr')));
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
