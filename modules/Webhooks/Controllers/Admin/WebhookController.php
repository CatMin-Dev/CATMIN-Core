<?php

namespace Modules\Webhooks\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Services\WebhookAdminService;
use Modules\Webhooks\Services\WebhookSecurityService;

class WebhookController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('modules/Webhooks/Views/index.blade.php'), [
            'currentPage' => 'webhooks',
            'webhooks' => WebhookAdminService::listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Webhooks/Views/create.blade.php'), [
            'currentPage' => 'webhooks',
            'availableEvents' => WebhookAdminService::availableEvents(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', 'max:100'],
            'secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        WebhookAdminService::create($validated);

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook créé avec succès.');
    }

    public function edit(int $webhook): View
    {
        $model = WebhookAdminService::find($webhook);

        if (!$model) {
            abort(404);
        }

        return view()->file(base_path('modules/Webhooks/Views/edit.blade.php'), [
            'currentPage' => 'webhooks',
            'webhook' => $model,
            'availableEvents' => WebhookAdminService::availableEvents(),
        ]);
    }

    public function update(Request $request, int $webhook): RedirectResponse
    {
        $model = WebhookAdminService::find($webhook);

        if (!$model) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', 'max:100'],
            'secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        WebhookAdminService::update($model, $validated);

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook mis à jour.');
    }

    public function destroy(int $webhook): RedirectResponse
    {
        $model = WebhookAdminService::find($webhook);

        if ($model) {
            WebhookAdminService::delete($model);
        }

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook supprimé.');
    }

    public function rotateSecret(int $webhook, WebhookSecurityService $securityService): RedirectResponse
    {
        $model = WebhookAdminService::find($webhook);

        if (!$model) {
            abort(404);
        }

        $securityService->initiateSecretRotation($model);

        return redirect()->route('admin.webhooks.edit', $webhook)
            ->with('success', 'Rotation du secret initiée. Le nouveau secret est en attente de validation pendant 24h. Copiez-le maintenant dans l\'encart ci-dessous avant validation.');
    }

    public function completeRotation(int $webhook, WebhookSecurityService $securityService): RedirectResponse
    {
        $model = WebhookAdminService::find($webhook);

        if (!$model) {
            abort(404);
        }

        if ($model->rotation_status !== 'pending') {
            return redirect()->route('admin.webhooks.edit', $webhook)
                ->with('error', 'Aucune rotation en attente pour ce webhook.');
        }

        $securityService->completeSecretRotation($model);

        return redirect()->route('admin.webhooks.edit', $webhook)
            ->with('success', 'Rotation du secret complétée. Le nouveau secret est maintenant actif.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $action = (string) $request->input('bulk_action', '');
        $ids = $request->input('bulk_select', []);

        if (empty($ids) || !is_array($ids)) {
            return redirect()
                ->back()
                ->with('error', 'Veuillez selectionner au moins un webhook.');
        }

        // Sanitize and validate IDs
        $ids = collect($ids)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return redirect()
                ->back()
                ->with('error', 'Identifiants invalides.');
        }

        // Check permission based on action
        $permissionMap = [
            'activate'   => 'module.webhooks.edit',
            'deactivate' => 'module.webhooks.edit',
            'delete'     => 'module.webhooks.delete',
        ];

        $permission = $permissionMap[$action] ?? null;
        if ($permission && !catmin_can($permission)) {
            abort(403);
        }

        $count = 0;
        match ($action) {
            'activate' => $count = WebhookAdminService::bulkActivate($ids),
            'deactivate' => $count = WebhookAdminService::bulkDeactivate($ids),
            'delete' => $count = WebhookAdminService::bulkDelete($ids),
            default => null,
        };

        $messages = [
            'activate' => sprintf('Webhooks actives: %d', $count),
            'deactivate' => sprintf('Webhooks desactives: %d', $count),
            'delete' => sprintf('Webhooks supprimes: %d', $count),
        ];

        return redirect()
            ->back()
            ->with('success', $messages[$action] ?? 'Action effectuee.');
    }
}
