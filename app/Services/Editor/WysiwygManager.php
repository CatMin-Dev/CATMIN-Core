<?php

namespace App\Services\Editor;

use App\Services\SettingService;
use Illuminate\Support\Arr;

class WysiwygManager
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_TOOLS = [
        'bold', 'italic', 'underline', 'strike',
        'align-left', 'align-center', 'align-right', 'align-justify',
        'ul', 'ol', 'blockquote', 'code-block',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'text-color', 'bg-color',
        'link', 'modal-link', 'bookmarks', 'clear', 'undo', 'redo',
        'panel',
    ];

    /**
     * @var array<int, callable(array<string, mixed>):array<int, array<string, mixed>>>
     */
    private static array $panelProviders = [];

    /**
     * @var array<int, callable(string, string, array<string, mixed>):bool|null>
     */
    private static array $fieldResolvers = [];

    /**
     * @param callable(array<string, mixed>):array<int, array<string, mixed>> $provider
     */
    public static function registerPanelProvider(callable $provider): void
    {
        self::$panelProviders[] = $provider;
    }

    /**
     * @param callable(string, string, array<string, mixed>):bool|null $resolver
     */
    public static function registerFieldResolver(callable $resolver): void
    {
        self::$fieldResolvers[] = $resolver;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function isFieldEnabled(string $scope, string $field, array $context = []): bool
    {
        return $this->fieldMode($scope, $field, $context) !== 'simple';
    }

    /**
     * @param array<string,mixed> $context
     */
    public function fieldMode(string $scope, string $field, array $context = []): string
    {
        if (!config('catmin_editor.enabled', true)) {
            return 'simple';
        }

        foreach (self::$fieldResolvers as $resolver) {
            try {
                $decision = $resolver($scope, $field, $context);
                if ($decision !== null) {
                    return (bool) $decision ? 'rich+assets' : 'simple';
                }
            } catch (\Throwable) {
                // Ignore failing resolver to keep editor integration robust.
            }
        }

        /** @var FieldEditorIntegrationService $integration */
        $integration = app(FieldEditorIntegrationService::class);
        $resolved = $integration->resolve($scope, $field, $context);

        $mode = (string) ($resolved['mode'] ?? 'simple');
        if ($mode !== 'simple') {
            return $mode;
        }

        // Legacy compatibility: static and dynamic field rules from previous editor versions.
        $fieldsConfig = (array) config('catmin_editor.fields', []);
        $nestedEnabled = (bool) data_get($fieldsConfig, $scope . '.' . $field, false);
        $flatEnabled = (bool) data_get((array) ($fieldsConfig[$scope] ?? []), $field, false);
        if ($nestedEnabled || $flatEnabled) {
            return 'rich+assets';
        }

        $needle = $scope . '.' . $field;
        foreach ($this->dynamicFieldRules() as $rule) {
            if (fnmatch($rule, $needle)) {
                return 'rich+assets';
            }
        }

        return 'simple';
    }

    /**
     * @param array<string,mixed> $context
     */
    public function fieldIntegration(string $scope, string $field, array $context = []): array
    {
        /** @var FieldEditorIntegrationService $integration */
        $integration = app(FieldEditorIntegrationService::class);

        return $integration->resolve($scope, $field, $context);
    }

    /**
     * @return array<int, string>
     */
    public function tools(): array
    {
        $setting = SettingService::get('addon.cat_wysiwyg.toolbar_tools', []);
        $configured = $this->toStringList($setting);
        if ($configured === []) {
            return self::DEFAULT_TOOLS;
        }

        return array_values(array_intersect(self::DEFAULT_TOOLS, $configured));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function panelItems(array $context = []): array
    {
        return $this->snippetItems($context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function snippetItems(array $context = []): array
    {
        if (isset($context['scope'], $context['field'])) {
            /** @var FieldEditorIntegrationService $integration */
            $integration = app(FieldEditorIntegrationService::class);
            $resolved = $integration->resolve((string) $context['scope'], (string) $context['field'], $context);

            if (is_array($resolved['snippets'] ?? null)) {
                return (array) $resolved['snippets'];
            }
        }

        $dynamic = SettingService::get('addon.cat_wysiwyg.snippets', []);
        $dynamicItems = [];
        foreach ((array) $dynamic as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) Arr::get($item, 'label', ''));
            $html = (string) Arr::get($item, 'html', '');
            if ($label === '' || trim($html) === '') {
                continue;
            }

            $dynamicItems[] = [
                'label' => $label,
                'html' => $html,
                'icon' => trim((string) Arr::get($item, 'icon', '')),
                'css' => trim((string) Arr::get($item, 'css', '')),
                'source' => 'addon.cat-wysiwyg',
            ];
        }

        if ($dynamicItems !== []) {
            return $dynamicItems;
        }

        return $this->defaultSectionItems('snippets', $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function paragraphItems(array $context = []): array
    {
        return $this->defaultSectionItems('paragraphs', $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function blockItems(array $context = []): array
    {
        if (isset($context['scope'], $context['field'])) {
            /** @var FieldEditorIntegrationService $integration */
            $integration = app(FieldEditorIntegrationService::class);
            $resolved = $integration->resolve((string) $context['scope'], (string) $context['field'], $context);

            if (is_array($resolved['blocks'] ?? null)) {
                return (array) $resolved['blocks'];
            }
        }

        $dynamic = SettingService::get('addon.cat_wysiwyg.blocks', []);
        $dynamicItems = [];
        foreach ((array) $dynamic as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) Arr::get($item, 'label', ''));
            $html = (string) Arr::get($item, 'html', '');
            if ($label === '' || trim($html) === '') {
                continue;
            }

            $dynamicItems[] = [
                'label' => $label,
                'html' => $html,
                'icon' => trim((string) Arr::get($item, 'icon', '')),
                'css' => trim((string) Arr::get($item, 'css', '')),
                'source' => 'addon.cat-wysiwyg',
            ];
        }

        if ($dynamicItems !== []) {
            return $dynamicItems;
        }

        return $this->defaultSectionItems('blocks', $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function defaultPanelItems(array $context = []): array
    {
        return $this->defaultSectionItems('snippets', $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function defaultSectionItems(string $section, array $context = []): array
    {
        $items = [];
        $configKey = match ($section) {
            'paragraphs' => 'catmin_editor.paragraphs',
            'blocks' => 'catmin_editor.blocks',
            default => 'catmin_editor.snippets',
        };

        foreach ((array) config($configKey, []) as $snippet) {
            if (!is_array($snippet)) {
                continue;
            }

            $label = trim((string) Arr::get($snippet, 'label', ''));
            $html = (string) Arr::get($snippet, 'html', '');

            if ($label === '' || trim($html) === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'html' => $html,
                'icon' => trim((string) Arr::get($snippet, 'icon', '')),
                'css' => trim((string) Arr::get($snippet, 'css', '')),
                'source' => 'core',
            ];
        }

        foreach (self::$panelProviders as $provider) {
            try {
                $provided = $provider($context);
            } catch (\Throwable) {
                $provided = [];
            }

            foreach ((array) $provided as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $label = trim((string) Arr::get($item, 'label', ''));
                $html = (string) Arr::get($item, 'html', '');
                if ($label === '' || trim($html) === '') {
                    continue;
                }

                $items[] = [
                    'label' => $label,
                    'html' => $html,
                    'icon' => trim((string) Arr::get($item, 'icon', '')),
                    'css' => trim((string) Arr::get($item, 'css', '')),
                    'source' => (string) Arr::get($item, 'source', 'extension'),
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function dynamicFieldRules(): array
    {
        $setting = SettingService::get('addon.cat_wysiwyg.enabled_fields', []);

        return $this->toStringList($setting);
    }

    /**
     * @return array<int, string>
     */
    private function toStringList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }
}
