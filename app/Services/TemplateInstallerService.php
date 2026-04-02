<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Articles\Models\Article;
use Modules\Blocks\Models\Block;
use Modules\Media\Models\MediaAsset;
use Modules\Menus\Models\Menu;
use Modules\Menus\Models\MenuItem;
use Modules\Pages\Models\Page;

class TemplateInstallerService
{
    private const STATE_FILE = 'storage/template-state.json';

    /**
     * @return array<string, mixed>
     */
    public function listTemplates(): array
    {
        $templates = [];

        foreach (glob($this->templatesPath() . '/*.template.json') ?: [] as $path) {
            $raw = json_decode((string) File::get($path), true);
            if (!is_array($raw)) {
                $templates[] = [
                    'file' => basename($path),
                    'slug' => pathinfo((string) basename($path), PATHINFO_FILENAME),
                    'name' => basename($path),
                    'valid' => false,
                    'errors' => ['JSON invalide.'],
                    'warnings' => [],
                    'sections' => [],
                ];
                continue;
            }

            $validation = $this->validateTemplate($raw);
            $normalized = (array) ($validation['template'] ?? []);
            $payload = (array) ($normalized['payload'] ?? []);

            $templates[] = [
                'file' => basename($path),
                'slug' => (string) ($normalized['slug'] ?? pathinfo((string) basename($path), PATHINFO_FILENAME)),
                'name' => (string) ($normalized['name'] ?? basename($path)),
                'version' => (string) ($normalized['version'] ?? '1.0.0'),
                'description' => (string) ($normalized['description'] ?? ''),
                'valid' => (bool) ($validation['ok'] ?? false),
                'errors' => (array) ($validation['errors'] ?? []),
                'warnings' => (array) ($validation['warnings'] ?? []),
                'sections' => [
                    'pages' => count((array) ($payload['pages'] ?? [])),
                    'articles' => count((array) ($payload['articles'] ?? [])),
                    'menus' => count((array) ($payload['menus'] ?? [])),
                    'blocks' => count((array) ($payload['blocks'] ?? [])),
                    'settings' => count((array) ($payload['settings'] ?? [])),
                    'media_placeholders' => count((array) ($payload['media_placeholders'] ?? [])),
                ],
            ];
        }

        usort($templates, fn (array $a, array $b) => strcmp((string) $a['slug'], (string) $b['slug']));

        return [
            'ok' => true,
            'templates' => $templates,
            'count' => count($templates),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function installFromSlug(string $slug, array $options = []): array
    {
        $template = $this->readTemplateBySlug($slug);
        if (($template['ok'] ?? false) !== true) {
            return $template;
        }

        /** @var array<string, mixed> $normalized */
        $normalized = (array) ($template['template'] ?? []);

        return $this->applyTemplate($normalized, $options);
    }

    /**
     * @param array<string, mixed> $template
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function applyTemplate(array $template, array $options = []): array
    {
        $validation = $this->validateTemplate($template);
        if (($validation['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => 'Template invalide.',
                'errors' => (array) ($validation['errors'] ?? []),
                'warnings' => (array) ($validation['warnings'] ?? []),
            ];
        }

        /** @var array<string, mixed> $normalized */
        $normalized = (array) ($validation['template'] ?? []);
        /** @var array<string, mixed> $payload */
        $payload = (array) ($normalized['payload'] ?? []);

        $overwrite = (bool) ($options['overwrite'] ?? false);
        $source = (string) ($options['source'] ?? 'manual');

        $summary = [
            'pages' => 0,
            'articles' => 0,
            'menus' => 0,
            'menu_items' => 0,
            'blocks' => 0,
            'settings' => 0,
            'media_placeholders' => 0,
        ];

        try {
            DB::transaction(function () use ($payload, $overwrite, &$summary): void {
                foreach ((array) ($payload['pages'] ?? []) as $item) {
                    $summary['pages'] += $this->upsertPage((array) $item, $overwrite) ? 1 : 0;
                }

                foreach ((array) ($payload['articles'] ?? []) as $item) {
                    $summary['articles'] += $this->upsertArticle((array) $item, $overwrite) ? 1 : 0;
                }

                foreach ((array) ($payload['menus'] ?? []) as $item) {
                    $counts = $this->upsertMenu((array) $item, $overwrite);
                    $summary['menus'] += (int) $counts['menus'];
                    $summary['menu_items'] += (int) $counts['menu_items'];
                }

                foreach ((array) ($payload['blocks'] ?? []) as $item) {
                    $summary['blocks'] += $this->upsertBlock((array) $item, $overwrite) ? 1 : 0;
                }

                foreach ((array) ($payload['settings'] ?? []) as $item) {
                    $summary['settings'] += $this->upsertSetting((array) $item, $overwrite) ? 1 : 0;
                }

                foreach ((array) ($payload['media_placeholders'] ?? []) as $item) {
                    $summary['media_placeholders'] += $this->upsertMediaPlaceholder((array) $item, $overwrite) ? 1 : 0;
                }
            });
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Installation template echouee: ' . $e->getMessage(),
                'errors' => ['Transaction rollback.'],
                'warnings' => [],
            ];
        }

        $report = [
            'ok' => true,
            'template' => [
                'slug' => (string) ($normalized['slug'] ?? ''),
                'name' => (string) ($normalized['name'] ?? ''),
                'version' => (string) ($normalized['version'] ?? ''),
            ],
            'source' => $source,
            'overwrite' => $overwrite,
            'summary' => $summary,
            'installed_at' => now()->toIso8601String(),
        ];

        $this->writeState($report);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function readTemplateBySlug(string $slug): array
    {
        $path = $this->templatePathBySlug($slug);
        if ($path === null || !File::exists($path)) {
            return [
                'ok' => false,
                'message' => "Template {$slug} introuvable.",
                'errors' => ["Template {$slug} introuvable."],
                'warnings' => [],
            ];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'message' => "Template {$slug}: JSON invalide.",
                'errors' => ["Template {$slug}: JSON invalide."],
                'warnings' => [],
            ];
        }

        $validation = $this->validateTemplate($decoded);
        if (($validation['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => "Template {$slug} invalide.",
                'errors' => (array) ($validation['errors'] ?? []),
                'warnings' => (array) ($validation['warnings'] ?? []),
            ];
        }

        return [
            'ok' => true,
            'template' => (array) ($validation['template'] ?? []),
            'warnings' => (array) ($validation['warnings'] ?? []),
        ];
    }

    /**
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    public function validateTemplate(array $template): array
    {
        $validator = Validator::make($template, [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'version' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:4000'],
            'required_modules' => ['nullable', 'array'],
            'required_modules.*' => ['string', 'max:80'],
            'required_addons' => ['nullable', 'array'],
            'required_addons.*' => ['string', 'max:120'],
            'payload' => ['required', 'array'],
            'payload.pages' => ['nullable', 'array'],
            'payload.articles' => ['nullable', 'array'],
            'payload.menus' => ['nullable', 'array'],
            'payload.blocks' => ['nullable', 'array'],
            'payload.settings' => ['nullable', 'array'],
            'payload.media_placeholders' => ['nullable', 'array'],
        ]);

        $errors = [];
        $warnings = [];

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $errors[] = $error;
            }
        }

        $normalized = [
            'name' => (string) ($template['name'] ?? ''),
            'slug' => strtolower((string) ($template['slug'] ?? '')),
            'version' => (string) ($template['version'] ?? '1.0.0'),
            'description' => (string) ($template['description'] ?? ''),
            'required_modules' => array_values(array_unique(array_map('strtolower', (array) ($template['required_modules'] ?? [])))),
            'required_addons' => array_values(array_unique(array_map('strtolower', (array) ($template['required_addons'] ?? [])))),
            'payload' => [
                'pages' => array_values((array) (($template['payload']['pages'] ?? []) ?: [])),
                'articles' => array_values((array) (($template['payload']['articles'] ?? []) ?: [])),
                'menus' => array_values((array) (($template['payload']['menus'] ?? []) ?: [])),
                'blocks' => array_values((array) (($template['payload']['blocks'] ?? []) ?: [])),
                'settings' => array_values((array) (($template['payload']['settings'] ?? []) ?: [])),
                'media_placeholders' => array_values((array) (($template['payload']['media_placeholders'] ?? []) ?: [])),
            ],
        ];

        $errors = array_merge($errors, $this->validateSections($normalized));
        $errors = array_merge($errors, $this->validateCompatibility($normalized));

        if (
            count((array) $normalized['payload']['pages']) === 0
            && count((array) $normalized['payload']['articles']) === 0
            && count((array) $normalized['payload']['menus']) === 0
            && count((array) $normalized['payload']['blocks']) === 0
            && count((array) $normalized['payload']['settings']) === 0
            && count((array) $normalized['payload']['media_placeholders']) === 0
        ) {
            $warnings[] = 'Template vide: aucun payload de donnees detecte.';
        }

        return [
            'ok' => count($errors) === 0,
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'template' => $normalized,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToFile(string $slug, string $path, array $meta = []): array
    {
        $template = $this->buildExportTemplate($slug, $meta);
        $validation = $this->validateTemplate($template);

        if (($validation['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => 'Template export invalide.',
                'errors' => (array) ($validation['errors'] ?? []),
                'warnings' => (array) ($validation['warnings'] ?? []),
            ];
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        return [
            'ok' => true,
            'path' => $path,
            'template' => $template,
            'warnings' => (array) ($validation['warnings'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestReport(): ?array
    {
        $state = $this->readState();

        return isset($state['latest']) && is_array($state['latest']) ? $state['latest'] : null;
    }

    private function templatesPath(): string
    {
        return base_path('templates');
    }

    private function templatePathBySlug(string $slug): ?string
    {
        $normalized = strtolower(trim($slug));
        if ($normalized === '') {
            return null;
        }

        $path = $this->templatesPath() . '/' . $normalized . '.template.json';

        return File::exists($path) ? $path : null;
    }

    /**
     * @param array<string, mixed> $template
     * @return array<int, string>
     */
    private function validateCompatibility(array $template): array
    {
        $errors = [];

        foreach ((array) ($template['required_modules'] ?? []) as $moduleSlug) {
            $moduleSlug = strtolower((string) $moduleSlug);
            if ($moduleSlug === '') {
                continue;
            }

            if (!ModuleManager::exists($moduleSlug)) {
                $errors[] = "Module requis introuvable: {$moduleSlug}.";
                continue;
            }

            if (!ModuleManager::isDeclaredEnabled($moduleSlug)) {
                $errors[] = "Module requis desactive: {$moduleSlug}.";
            }
        }

        foreach ((array) ($template['required_addons'] ?? []) as $addonSlug) {
            $addonSlug = strtolower((string) $addonSlug);
            if ($addonSlug === '') {
                continue;
            }

            $addon = AddonManager::find($addonSlug);
            if ($addon === null) {
                $errors[] = "Addon requis introuvable: {$addonSlug}.";
                continue;
            }

            if (!(bool) ($addon->enabled ?? false)) {
                $errors[] = "Addon requis desactive: {$addonSlug}.";
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $template
     * @return array<int, string>
     */
    private function validateSections(array $template): array
    {
        $errors = [];
        $payload = (array) ($template['payload'] ?? []);

        foreach ((array) ($payload['pages'] ?? []) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['title'] ?? '')) === '' || trim((string) ($item['slug'] ?? '')) === '') {
                $errors[] = "payload.pages.{$index}: title et slug requis.";
            }
        }

        foreach ((array) ($payload['articles'] ?? []) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['title'] ?? '')) === '' || trim((string) ($item['slug'] ?? '')) === '') {
                $errors[] = "payload.articles.{$index}: title et slug requis.";
            }
        }

        foreach ((array) ($payload['menus'] ?? []) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['name'] ?? '')) === '' || trim((string) ($item['slug'] ?? '')) === '') {
                $errors[] = "payload.menus.{$index}: name et slug requis.";
            }
        }

        foreach ((array) ($payload['blocks'] ?? []) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['name'] ?? '')) === '' || trim((string) ($item['slug'] ?? '')) === '') {
                $errors[] = "payload.blocks.{$index}: name et slug requis.";
            }
        }

        foreach ((array) ($payload['settings'] ?? []) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['key'] ?? '')) === '') {
                $errors[] = "payload.settings.{$index}: key requis.";
            }
        }

        foreach ((array) ($payload['media_placeholders'] ?? []) as $index => $item) {
            if (!is_array($item) || trim((string) ($item['path'] ?? '')) === '' || trim((string) ($item['filename'] ?? '')) === '') {
                $errors[] = "payload.media_placeholders.{$index}: path et filename requis.";
            }
        }

        if (count((array) ($payload['pages'] ?? [])) > 0 && !Schema::hasTable('pages')) {
            $errors[] = 'Table pages manquante pour payload.pages.';
        }

        if (count((array) ($payload['articles'] ?? [])) > 0 && !Schema::hasTable('articles')) {
            $errors[] = 'Table articles manquante pour payload.articles.';
        }

        if (count((array) ($payload['menus'] ?? [])) > 0 && (!Schema::hasTable('menus') || !Schema::hasTable('menu_items'))) {
            $errors[] = 'Tables menus/menu_items manquantes pour payload.menus.';
        }

        if (count((array) ($payload['blocks'] ?? [])) > 0 && !Schema::hasTable('blocks')) {
            $errors[] = 'Table blocks manquante pour payload.blocks.';
        }

        if (count((array) ($payload['settings'] ?? [])) > 0 && !Schema::hasTable('settings')) {
            $errors[] = 'Table settings manquante pour payload.settings.';
        }

        if (count((array) ($payload['media_placeholders'] ?? [])) > 0 && !Schema::hasTable('media_assets')) {
            $errors[] = 'Table media_assets manquante pour payload.media_placeholders.';
        }

        return $errors;
    }

    private function upsertPage(array $item, bool $overwrite): bool
    {
        $attributes = [
            'title' => (string) ($item['title'] ?? ''),
            'excerpt' => (string) ($item['excerpt'] ?? ''),
            'content' => (string) ($item['content'] ?? ''),
            'status' => (string) ($item['status'] ?? 'published'),
            'published_at' => $this->normalizeDate($item['published_at'] ?? null),
            'meta_title' => (string) ($item['meta_title'] ?? ''),
            'meta_description' => (string) ($item['meta_description'] ?? ''),
            'media_asset_id' => null,
        ];

        if ($overwrite) {
            Page::query()->updateOrCreate(['slug' => (string) $item['slug']], $attributes);
            return true;
        }

        Page::query()->firstOrCreate(['slug' => (string) $item['slug']], array_merge(['slug' => (string) $item['slug']], $attributes));
        return true;
    }

    private function upsertArticle(array $item, bool $overwrite): bool
    {
        $attributes = [
            'title' => (string) ($item['title'] ?? ''),
            'excerpt' => (string) ($item['excerpt'] ?? ''),
            'content' => (string) ($item['content'] ?? ''),
            'content_type' => (string) ($item['content_type'] ?? 'article'),
            'status' => (string) ($item['status'] ?? 'published'),
            'published_at' => $this->normalizeDate($item['published_at'] ?? null),
            'meta_title' => (string) ($item['meta_title'] ?? ''),
            'meta_description' => (string) ($item['meta_description'] ?? ''),
            'article_category_id' => null,
            'media_asset_id' => null,
            'seo_meta_id' => null,
            'taxonomy_snapshot' => ['category' => null, 'tags' => []],
        ];

        if ($overwrite) {
            Article::query()->updateOrCreate(['slug' => (string) $item['slug']], $attributes);
            return true;
        }

        Article::query()->firstOrCreate(['slug' => (string) $item['slug']], array_merge(['slug' => (string) $item['slug']], $attributes));
        return true;
    }

    /**
     * @return array{menus:int,menu_items:int}
     */
    private function upsertMenu(array $item, bool $overwrite): array
    {
        $menu = Menu::query()->updateOrCreate(
            ['slug' => (string) ($item['slug'] ?? '')],
            [
                'name' => (string) ($item['name'] ?? ''),
                'location' => (string) ($item['location'] ?? 'primary'),
                'status' => (string) ($item['status'] ?? 'active'),
            ]
        );

        if ($overwrite) {
            MenuItem::query()->where('menu_id', $menu->id)->delete();
        }

        $createdItems = 0;
        $refMap = [];

        foreach ((array) ($item['items'] ?? []) as $line) {
            $line = (array) $line;

            $type = (string) ($line['type'] ?? 'url');
            $url = (string) ($line['url'] ?? '');
            $pageId = null;

            if ($type === 'page') {
                $pageSlug = trim((string) ($line['page_slug'] ?? ''));
                $page = $pageSlug !== '' ? Page::query()->where('slug', $pageSlug)->first() : null;
                $pageId = $page?->id;
                $url = $page ? '/page/' . $page->slug : $url;
            }

            $parentId = null;
            $parentRef = trim((string) ($line['parent_ref'] ?? ''));
            if ($parentRef !== '' && isset($refMap[$parentRef])) {
                $parentId = (int) $refMap[$parentRef];
            }

            $created = MenuItem::query()->create([
                'menu_id' => $menu->id,
                'parent_id' => $parentId,
                'label' => (string) ($line['label'] ?? ''),
                'url' => $url !== '' ? $url : null,
                'page_id' => $pageId,
                'type' => $type,
                'sort_order' => (int) ($line['sort_order'] ?? 0),
                'status' => (string) ($line['status'] ?? 'active'),
            ]);

            $createdItems++;
            $ref = trim((string) ($line['ref'] ?? ''));
            if ($ref !== '') {
                $refMap[$ref] = $created->id;
            }
        }

        return ['menus' => 1, 'menu_items' => $createdItems];
    }

    private function upsertBlock(array $item, bool $overwrite): bool
    {
        $attributes = [
            'name' => (string) ($item['name'] ?? ''),
            'content' => (string) ($item['content'] ?? ''),
            'status' => (string) ($item['status'] ?? 'active'),
        ];

        if ($overwrite) {
            Block::query()->updateOrCreate(['slug' => (string) $item['slug']], $attributes);
            return true;
        }

        Block::query()->firstOrCreate(['slug' => (string) $item['slug']], array_merge(['slug' => (string) $item['slug']], $attributes));
        return true;
    }

    private function upsertSetting(array $item, bool $overwrite): bool
    {
        $key = (string) ($item['key'] ?? '');
        $value = $item['value'] ?? '';

        $attributes = [];

        $resolved = [
            'label' => (string) ($item['label'] ?? $key),
            'value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES),
            'type' => (string) ($item['type'] ?? 'string'),
            'group' => (string) ($item['group'] ?? 'template'),
            'description' => (string) ($item['description'] ?? ''),
            'is_public' => (bool) ($item['is_public'] ?? false),
            'is_editable' => (bool) ($item['is_editable'] ?? true),
            'options' => isset($item['options']) && !is_scalar($item['options'])
                ? json_encode($item['options'], JSON_UNESCAPED_SLASHES)
                : (string) ($item['options'] ?? ''),
            'validation_rules' => isset($item['validation_rules']) && !is_scalar($item['validation_rules'])
                ? json_encode($item['validation_rules'], JSON_UNESCAPED_SLASHES)
                : (string) ($item['validation_rules'] ?? ''),
        ];

        foreach ($resolved as $column => $columnValue) {
            if (Schema::hasColumn('settings', $column)) {
                $attributes[$column] = $columnValue;
            }
        }

        if ($overwrite) {
            Setting::query()->updateOrCreate(['key' => $key], $attributes);
            return true;
        }

        Setting::query()->firstOrCreate(['key' => $key], array_merge(['key' => $key], $attributes));
        return true;
    }

    private function upsertMediaPlaceholder(array $item, bool $overwrite): bool
    {
        $path = (string) ($item['path'] ?? '');

        $attributes = [
            'disk' => (string) ($item['disk'] ?? 'public'),
            'filename' => (string) ($item['filename'] ?? basename($path)),
            'original_name' => (string) ($item['original_name'] ?? ($item['filename'] ?? basename($path))),
            'mime_type' => (string) ($item['mime_type'] ?? 'image/jpeg'),
            'extension' => (string) ($item['extension'] ?? pathinfo((string) ($item['filename'] ?? basename($path)), PATHINFO_EXTENSION)),
            'size_bytes' => (int) ($item['size_bytes'] ?? 0),
            'alt_text' => (string) ($item['alt_text'] ?? ''),
            'caption' => (string) ($item['caption'] ?? ''),
            'metadata' => [
                'placeholder' => true,
                'template_source' => (string) ($item['template_source'] ?? 'template-installer'),
            ],
            'uploaded_by_id' => null,
        ];

        if ($overwrite) {
            MediaAsset::query()->updateOrCreate(['path' => $path], $attributes);
            return true;
        }

        MediaAsset::query()->firstOrCreate(['path' => $path], array_merge(['path' => $path], $attributes));
        return true;
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function buildExportTemplate(string $slug, array $meta): array
    {
        $name = trim((string) ($meta['name'] ?? ''));
        if ($name === '') {
            $name = 'CATMIN Export Template';
        }

        $description = trim((string) ($meta['description'] ?? ''));
        if ($description === '') {
            $description = 'Template exporte depuis une instance CATMIN.';
        }

        $pages = Page::query()
            ->orderBy('id')
            ->get(['title', 'slug', 'excerpt', 'content', 'status', 'published_at', 'meta_title', 'meta_description'])
            ->map(fn (Page $page) => [
                'title' => $page->title,
                'slug' => $page->slug,
                'excerpt' => (string) ($page->excerpt ?? ''),
                'content' => (string) ($page->content ?? ''),
                'status' => (string) ($page->status ?? 'draft'),
                'published_at' => optional($page->published_at)?->toDateTimeString(),
                'meta_title' => (string) ($page->meta_title ?? ''),
                'meta_description' => (string) ($page->meta_description ?? ''),
            ])
            ->values()
            ->all();

        $articles = Article::query()
            ->orderBy('id')
            ->get(['title', 'slug', 'excerpt', 'content', 'content_type', 'status', 'published_at', 'meta_title', 'meta_description'])
            ->map(fn (Article $article) => [
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => (string) ($article->excerpt ?? ''),
                'content' => (string) ($article->content ?? ''),
                'content_type' => (string) ($article->content_type ?? 'article'),
                'status' => (string) ($article->status ?? 'draft'),
                'published_at' => optional($article->published_at)?->toDateTimeString(),
                'meta_title' => (string) ($article->meta_title ?? ''),
                'meta_description' => (string) ($article->meta_description ?? ''),
            ])
            ->values()
            ->all();

        $menus = Menu::query()
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(function (Menu $menu): array {
                $items = [];
                $refById = [];

                foreach ($menu->items as $item) {
                    $refById[$item->id] = 'item_' . $item->id;
                }

                foreach ($menu->items as $item) {
                    $items[] = [
                        'ref' => $refById[$item->id] ?? null,
                        'parent_ref' => $item->parent_id ? ($refById[$item->parent_id] ?? null) : null,
                        'label' => $item->label,
                        'type' => $item->type,
                        'url' => (string) ($item->url ?? ''),
                        'page_slug' => $item->page_id ? optional(Page::query()->find($item->page_id))->slug : null,
                        'sort_order' => (int) ($item->sort_order ?? 0),
                        'status' => (string) ($item->status ?? 'active'),
                    ];
                }

                return [
                    'name' => $menu->name,
                    'slug' => $menu->slug,
                    'location' => (string) ($menu->location ?? 'primary'),
                    'status' => (string) ($menu->status ?? 'active'),
                    'items' => $items,
                ];
            })
            ->values()
            ->all();

        $blocks = Block::query()
            ->orderBy('id')
            ->get(['name', 'slug', 'content', 'status'])
            ->map(fn (Block $block) => [
                'name' => $block->name,
                'slug' => $block->slug,
                'content' => (string) ($block->content ?? ''),
                'status' => (string) ($block->status ?? 'active'),
            ])
            ->values()
            ->all();

        $settingColumns = ['key', 'value'];
        foreach (['label', 'type', 'group', 'description', 'is_public', 'is_editable'] as $column) {
            if (Schema::hasColumn('settings', $column)) {
                $settingColumns[] = $column;
            }
        }

        $settings = Setting::query()
            ->orderBy('id')
            ->get($settingColumns)
            ->map(fn (Setting $setting) => [
                'key' => $setting->key,
                'label' => (string) ($setting->label ?? $setting->key),
                'value' => $setting->value,
                'type' => (string) ($setting->type ?? 'string'),
                'group' => (string) ($setting->group ?? 'general'),
                'description' => (string) ($setting->description ?? ''),
                'is_public' => (bool) ($setting->is_public ?? false),
                'is_editable' => (bool) ($setting->is_editable ?? true),
            ])
            ->values()
            ->all();

        $mediaPlaceholders = MediaAsset::query()
            ->orderBy('id')
            ->get(['disk', 'path', 'filename', 'original_name', 'mime_type', 'extension', 'size_bytes', 'alt_text', 'caption'])
            ->map(fn (MediaAsset $asset) => [
                'disk' => (string) ($asset->disk ?? 'public'),
                'path' => (string) $asset->path,
                'filename' => (string) $asset->filename,
                'original_name' => (string) ($asset->original_name ?? $asset->filename),
                'mime_type' => (string) ($asset->mime_type ?? 'application/octet-stream'),
                'extension' => (string) ($asset->extension ?? ''),
                'size_bytes' => (int) ($asset->size_bytes ?? 0),
                'alt_text' => (string) ($asset->alt_text ?? ''),
                'caption' => (string) ($asset->caption ?? ''),
            ])
            ->values()
            ->all();

        return [
            'name' => $name,
            'slug' => strtolower(trim($slug)),
            'version' => '1.0.0',
            'description' => $description,
            'required_modules' => ['core', 'settings', 'pages', 'articles', 'menus', 'blocks', 'media'],
            'required_addons' => [],
            'payload' => [
                'pages' => $pages,
                'articles' => $articles,
                'menus' => $menus,
                'blocks' => $blocks,
                'settings' => $settings,
                'media_placeholders' => $mediaPlaceholders,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $latest
     */
    private function writeState(array $latest): void
    {
        $path = base_path(self::STATE_FILE);
        File::ensureDirectoryExists(dirname($path));

        $state = $this->readState();
        $history = (array) ($state['history'] ?? []);

        array_unshift($history, $latest);
        $history = array_slice($history, 0, 20);

        File::put($path, json_encode([
            'latest' => $latest,
            'history' => $history,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(): array
    {
        $path = base_path(self::STATE_FILE);
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
