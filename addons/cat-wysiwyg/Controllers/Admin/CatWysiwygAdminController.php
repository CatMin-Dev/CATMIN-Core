<?php

namespace Addons\CatWysiwyg\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatWysiwygAdminController extends Controller
{
    public function index(): View
    {
        $config = require base_path('addons/cat-wysiwyg/config.php');
        $defaults = (array) ($config['defaults'] ?? []);
        $manager = app(\App\Services\Editor\WysiwygManager::class);

        $toolbar = $this->decodeArraySetting('addon.cat_wysiwyg.toolbar_tools', (array) ($defaults['toolbar_tools'] ?? []));
        $fields = $this->decodeArraySetting('addon.cat_wysiwyg.enabled_fields', (array) ($defaults['enabled_fields'] ?? []));
        $snippets = $manager->snippetItems();
        $blocks = $manager->blockItems();

        return view('addon_cat_wysiwyg::admin.index', [
            'toolbarTools' => $toolbar,
            'enabledFields' => $fields,
            'snippets' => $snippets,
            'blocks' => $blocks,
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
            'blocks_json' => ['nullable', 'string'],
            'snippets_rows' => ['nullable', 'array'],
            'snippets_rows.*.label' => ['nullable', 'string', 'max:150'],
            'snippets_rows.*.icon' => ['nullable', 'string', 'max:255'],
            'snippets_rows.*.css' => ['nullable', 'string'],
            'snippets_rows.*.html' => ['nullable', 'string'],
            'blocks_rows' => ['nullable', 'array'],
            'blocks_rows.*.label' => ['nullable', 'string', 'max:150'],
            'blocks_rows.*.icon' => ['nullable', 'string', 'max:255'],
            'blocks_rows.*.css' => ['nullable', 'string'],
            'blocks_rows.*.html' => ['nullable', 'string'],
        ]);

        $toolbarTools = array_values(array_unique(array_map('strval', (array) ($validated['toolbar_tools'] ?? []))));

        $fieldsRaw = (string) ($validated['enabled_fields'] ?? '');
        $enabledFields = collect(preg_split('/\r\n|\r|\n/', $fieldsRaw) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();

        $snippets = $this->normalizeLibraryItems(
            $this->resolveLibraryInput((string) ($validated['snippets_json'] ?? ''), (array) ($validated['snippets_rows'] ?? []), 'snippets_json')
        );

        $blocks = $this->normalizeLibraryItems(
            $this->resolveLibraryInput((string) ($validated['blocks_json'] ?? ''), (array) ($validated['blocks_rows'] ?? []), 'blocks_json')
        );

        SettingService::put('addon.cat_wysiwyg.toolbar_tools', $toolbarTools, 'json', 'addons', 'Toolbar tools WYSIWYG');
        SettingService::put('addon.cat_wysiwyg.enabled_fields', $enabledFields, 'json', 'addons', 'Champs actives WYSIWYG');
        SettingService::put('addon.cat_wysiwyg.snippets', $snippets, 'json', 'addons', 'Snippets WYSIWYG');
        SettingService::put('addon.cat_wysiwyg.blocks', $blocks, 'json', 'addons', 'Blocs WYSIWYG');

        return redirect()->route('admin.addon.cat_wysiwyg.index')->with('status', 'Configuration WYSIWYG mise a jour.');
    }

    public function library(): JsonResponse
    {
        $config = require base_path('addons/cat-wysiwyg/config.php');
        $defaults = (array) ($config['defaults'] ?? []);

        $snippets = $this->decodeArraySetting('addon.cat_wysiwyg.snippets', (array) ($defaults['snippets'] ?? []));
        $blocks = $this->decodeArraySetting('addon.cat_wysiwyg.blocks', (array) ($defaults['blocks'] ?? []));

        return response()->json([
            'snippets' => $this->normalizeLibraryItems($snippets),
            'blocks' => $this->normalizeLibraryItems($blocks),
        ]);
    }

    public function updateLibrary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'snippets' => ['nullable', 'array'],
            'snippets.*.label' => ['nullable', 'string', 'max:150'],
            'snippets.*.icon' => ['nullable', 'string', 'max:80'],
            'snippets.*.css' => ['nullable', 'string'],
            'snippets.*.html' => ['nullable', 'string'],
            'blocks' => ['nullable', 'array'],
            'blocks.*.label' => ['nullable', 'string', 'max:150'],
            'blocks.*.icon' => ['nullable', 'string', 'max:80'],
            'blocks.*.css' => ['nullable', 'string'],
            'blocks.*.html' => ['nullable', 'string'],
        ]);

        $snippets = $this->normalizeLibraryItems((array) ($validated['snippets'] ?? []));
        $blocks = $this->normalizeLibraryItems((array) ($validated['blocks'] ?? []));

        SettingService::put('addon.cat_wysiwyg.snippets', $snippets, 'json', 'addons', 'Snippets WYSIWYG');
        SettingService::put('addon.cat_wysiwyg.blocks', $blocks, 'json', 'addons', 'Blocs WYSIWYG');

        return response()->json([
            'ok' => true,
            'message' => 'Bibliotheque WYSIWYG mise a jour.',
            'snippets_count' => count($snippets),
            'blocks_count' => count($blocks),
        ]);
    }

    /**
     * @param array<int, mixed> $items
    * @return array<int, array{label:string,icon:string,css:string,html:string}>
     */
    private function normalizeLibraryItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return [
                    'label' => trim((string) ($item['label'] ?? '')),
                    'icon' => trim((string) ($item['icon'] ?? '')),
                    'css' => trim((string) ($item['css'] ?? '')),
                    'html' => trim((string) ($item['html'] ?? '')),
                ];
            })
            ->filter(fn (array $item) => $item['label'] !== '' && $item['html'] !== '')
            ->unique(fn (array $item) => mb_strtolower($item['label']) . '|' . $item['html'])
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $rows
     * @return array<int, mixed>
     */
    private function resolveLibraryInput(string $jsonPayload, array $rows, string $field): array
    {
        if ($rows !== []) {
            return $rows;
        }

        $jsonPayload = trim($jsonPayload);
        if ($jsonPayload === '') {
            return [];
        }

        $decoded = json_decode($jsonPayload, true);
        if (!is_array($decoded)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => sprintf('JSON %s invalide.', str_replace('_json', '', $field)),
            ]);
        }

        return $decoded;
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
