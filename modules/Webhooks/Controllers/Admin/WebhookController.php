<?php

namespace Modules\Webhooks\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Services\WebhookAdminService;

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
}
