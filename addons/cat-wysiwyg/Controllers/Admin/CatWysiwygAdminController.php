<?php

namespace Addons\CatWysiwyg\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatWysiwygAdminController extends Controller
{
    public function index(): View
    {
        $config = require base_path('addons/cat-wysiwyg/config.php');
        $defaults = (array) ($config['defaults'] ?? []);

        $toolbar = $this->decodeArraySetting('addon.cat_wysiwyg.toolbar_tools', (array) ($defaults['toolbar_tools'] ?? []));
        $fields = $this->decodeArraySetting('addon.cat_wysiwyg.enabled_fields', (array) ($defaults['enabled_fields'] ?? []));
        $snippets = $this->decodeArraySetting('addon.cat_wysiwyg.snippets', (array) ($defaults['snippets'] ?? []));

        return view('addon_cat_wysiwyg::admin.index', [
            'toolbarTools' => $toolbar,
            'enabledFields' => $fields,
            'snippetsJson' => json_encode($snippets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'allTools' => (array) ($defaults['toolbar_tools'] ?? []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'toolbar_tools' => ['nullable', 'array'],
            'toolbar_tools.*' => ['string', 'max:64'],
            'enabled_fields' => ['nullable', 'string'],
            'snippets_json' => ['nullable', 'string'],
        ]);

        $toolbarTools = array_values(array_unique(array_map('strval', (array) ($validated['toolbar_tools'] ?? []))));

        $fieldsRaw = (string) ($validated['enabled_fields'] ?? '');
        $enabledFields = collect(preg_split('/\r\n|\r|\n/', $fieldsRaw) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();

        $snippetsRaw = trim((string) ($validated['snippets_json'] ?? '[]'));
        $decoded = json_decode($snippetsRaw, true);
        if (!is_array($decoded)) {
            return back()->withErrors(['snippets_json' => 'JSON snippets invalide.'])->withInput();
        }

        $snippets = collect($decoded)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return [
                    'label' => trim((string) ($item['label'] ?? '')),
                    'html' => (string) ($item['html'] ?? ''),
                ];
            })
            ->filter(fn (array $item) => $item['label'] !== '' && trim($item['html']) !== '')
            ->values()
            ->all();

        SettingService::put('addon.cat_wysiwyg.toolbar_tools', $toolbarTools, 'json', 'addons', 'Toolbar tools WYSIWYG');
        SettingService::put('addon.cat_wysiwyg.enabled_fields', $enabledFields, 'json', 'addons', 'Champs actives WYSIWYG');
        SettingService::put('addon.cat_wysiwyg.snippets', $snippets, 'json', 'addons', 'Snippets WYSIWYG');

        return redirect()->route('admin.addon.cat_wysiwyg.index')->with('status', 'Configuration WYSIWYG mise a jour.');
    }

    /**
     * @param array<int, mixed> $default
     * @return array<int, mixed>
     */
    private function decodeArraySetting(string $key, array $default): array
    {
        $value = SettingService::get($key, $default);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return $default;
        }

        return is_array($value) ? $value : $default;
    }
}
