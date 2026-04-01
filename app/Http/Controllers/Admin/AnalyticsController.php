<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function index(Request $request): View
    {
        $days = (int) $request->integer('days', 7);

        return view('admin.pages.analytics.index', [
            'currentPage' => 'analytics',
            'report' => $this->analyticsService->dashboard($days),
            'settings' => [
                'enabled' => $this->analyticsService->isEnabled(),
                'retention_days' => $this->analyticsService->retentionDays(),
                'anonymous_mode' => (bool) \App\Services\SettingService::get('analytics.anonymous_mode', config('catmin.analytics.anonymous_mode', true)),
                'modules_tracked' => (array) \App\Services\SettingService::get('analytics.modules_tracked', config('catmin.analytics.modules_tracked', ['*'])),
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'in:0,1'],
            'anonymous_mode' => ['nullable', 'in:0,1'],
            'retention_days' => ['required', 'integer', 'min:7', 'max:365'],
            'modules_tracked' => ['nullable', 'string'],
        ]);

        $enabled = ((string) ($validated['enabled'] ?? '0')) === '1';
        $anonymous = ((string) ($validated['anonymous_mode'] ?? '1')) === '1';
        $retentionDays = (int) $validated['retention_days'];
        $modulesRaw = trim((string) ($validated['modules_tracked'] ?? '*'));

        $modules = collect(explode(',', $modulesRaw))
            ->map(fn ($item) => trim(strtolower($item)))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();

        if ($modules === []) {
            $modules = ['*'];
        }

        $this->persistSetting('analytics.enabled', $enabled ? '1' : '0', 'boolean', 'Active la collecte analytics interne');
        $this->persistSetting('analytics.anonymous_mode', $anonymous ? '1' : '0', 'boolean', 'Anonymise l acteur analytics');
        $this->persistSetting('analytics.retention_days', (string) $retentionDays, 'integer', 'Duree retention analytics en jours');
        $this->persistSetting('analytics.modules_tracked', json_encode($modules, JSON_UNESCAPED_SLASHES), 'json', 'Domaines analytics suivis');

        \App\Services\SettingService::forgetCache();

        return redirect()->route('admin.analytics.index')->with('success', 'Settings analytics mises a jour.');
    }

    private function persistSetting(string $key, ?string $value, string $type, string $description): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $columns = Schema::getColumnListing('settings');
        $payload = [
            'value' => $value,
            'type' => $type,
            'group' => 'analytics',
            'description' => $description,
            'updated_at' => now(),
        ];

        if (in_array('is_public', $columns, true)) {
            $payload['is_public'] = false;
        }

        if (in_array('label', $columns, true)) {
            $payload['label'] = $key;
        }

        if (in_array('is_editable', $columns, true)) {
            $payload['is_editable'] = true;
        }

        if (in_array('options', $columns, true)) {
            $payload['options'] = null;
        }

        if (in_array('validation_rules', $columns, true)) {
            $payload['validation_rules'] = null;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            array_merge($payload, ['created_at' => now()])
        );
    }
}
