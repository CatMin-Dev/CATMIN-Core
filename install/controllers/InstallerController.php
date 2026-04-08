<?php

declare(strict_types=1);

namespace Install\controllers;

use Core\http\Request;
use Core\http\Response;
use Core\http\View;
use Core\security\CsrfManager;
require_once CATMIN_CORE . '/error-dispatcher.php';
use Install\InstallerEngine;
use Install\InstallerStateMachine;

final class InstallerController
{
    private InstallerEngine $engine;
    private InstallerStateMachine $state;

    public function __construct()
    {
        $this->engine = new InstallerEngine();
        $this->state = new InstallerStateMachine();
    }

    public function root(): Response
    {
        if ($this->engine->isLocked()) {
            return $this->redirectToAdminLogin();
        }

        return Response::html('', 302, ['Location' => $this->stepUrl($this->engine->firstAccessibleStep())]);
    }

    public function showStep(Request $request): Response
    {
        if ($this->engine->isLocked()) {
            return $this->redirectToAdminLogin();
        }

        $requested = $this->resolveRequestedStep($request);
        $firstAccessible = $this->engine->firstAccessibleStep();

        if (!$this->state->hasStep($requested)) {
            return Response::html('', 302, ['Location' => $this->stepUrl($firstAccessible)]);
        }

        $context = $this->engine->context();
        if (!$this->state->canAccess($requested, $context)) {
            return Response::html('', 302, ['Location' => $this->stepUrl($firstAccessible)]);
        }

        $definition = require CATMIN_INSTALL . '/steps/' . $requested . '.php';

        return View::make('wizard', [
            'step' => $requested,
            'definition' => $definition,
            'context' => $context,
            'steps' => InstallerStateMachine::STEPS,
            'error' => null,
            'adminPath' => '/' . trim((string) config('security.admin_path', 'admin'), '/'),
        ], 'install');
    }

    public function submitStep(Request $request): Response
    {
        if ($this->engine->isLocked()) {
            return $this->redirectToAdminLogin();
        }

        $step = (string) $request->input('_step', '');
        $payload = $request->post();
        unset($payload['_step'], $payload['_csrf']);

        $result = $this->engine->saveStep($step, $payload);

        if (!($result['ok'] ?? false)) {
            $context = $this->engine->context();
            $definition = require CATMIN_INSTALL . '/steps/' . $step . '.php';

            return View::make('wizard', [
                'step' => $step,
                'definition' => $definition,
                'context' => $context,
                'steps' => InstallerStateMachine::STEPS,
                'error' => (string) ($result['message'] ?? 'Validation error.'),
                'adminPath' => '/' . trim((string) config('security.admin_path', 'admin'), '/'),
            ], 'install', 422);
        }

        $next = (string) ($result['redirect_step'] ?? 'lock');

        if ($next === 'lock' && $step === 'lock') {
            return $this->redirectToAdminLogin();
        }

        if ($next === 'report') {
            return Response::html('', 302, ['Location' => '/install/report']);
        }

        return Response::html('', 302, ['Location' => $this->stepUrl($next)]);
    }

    public function showReport(): Response
    {
        if ($this->engine->isLocked()) {
            return $this->redirectToAdminLogin();
        }

        $context = $this->engine->context();

        return View::make('report', [
            'context' => $context,
            'adminPath' => '/' . trim((string) config('security.admin_path', 'admin'), '/'),
        ], 'install');
    }

    public function testDatabase(Request $request): Response
    {
        if ($this->engine->isLocked()) {
            return Response::json([
                'ok' => false,
                'message' => 'Installer locked.',
                'csrf' => (new CsrfManager())->token(),
            ], 423);
        }

        $payload = $request->post();
        unset($payload['_csrf']);

        $result = $this->engine->testDatabaseConnection($payload);
        $result['csrf'] = (new CsrfManager())->token();
        $status = ($result['ok'] ?? false) ? 200 : 422;

        return Response::json($result, $status);
    }

    public function reset(Request $request): Response
    {
        if ($this->engine->isLocked()) {
            return $this->redirectToAdminLogin();
        }

        $this->engine->resetProgress();

        return Response::html('', 302, ['Location' => $this->stepUrl('precheck')]);
    }

    private function redirectToAdminLogin(): Response
    {
        $adminPath = '/' . trim((string) config('security.admin_path', 'admin'), '/');
        return Response::html('', 302, ['Location' => $adminPath . '/login']);
    }

    private function resolveRequestedStep(Request $request): string
    {
        $query = $request->query();
        $fromQuery = isset($query['s']) ? (string) $query['s'] : '';
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        $path = $request->path();
        if (str_starts_with($path, '/step/')) {
            return (string) trim(substr($path, strlen('/step/')), '/');
        }

        return $this->engine->firstAccessibleStep();
    }

    private function stepUrl(string $step): string
    {
        return '/install/step/' . rawurlencode($step);
    }
}
