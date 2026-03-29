<?php

namespace Modules\Docs\Services;

use App\Services\SettingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class DocsService
{
    private const DEFAULT_VERSION = 'current';

    /**
     * Base directory for global documentation files.
     */
    public function docsPath(): string
    {
        return base_path('docs-site');
    }

    public function modulesPath(): string
    {
        return base_path('modules');
    }

    /**
     * Convert a Markdown string to safe HTML.
     */
    public function toHtml(string $markdown): string
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());

        $converter = new MarkdownConverter($environment);

        return (string) $converter->convert($markdown);
    }

    /**
     * Return all .md files in the docs directory, indexed by slug.
     *
     * @return array<string, array<string, mixed>>
     */
    public function index(): array
    {
        $cacheKey = $this->indexCacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(10), function (): array {
            $docs = [];

            $this->scanDirectory($this->docsPath(), null, $docs);

            $modulesPath = $this->modulesPath();
            foreach (glob($modulesPath . '/*/docs') ?: [] as $modDocsDir) {
                $moduleName = basename(dirname($modDocsDir));
                $this->scanDirectory($modDocsDir, strtolower($moduleName), $docs);
            }
            foreach (glob($modulesPath . '/*/HELP.md') ?: [] as $helpFile) {
                $moduleName = basename(dirname($helpFile));
                $slug = 'help-' . strtolower($moduleName);
                $meta = $this->readMetadata($helpFile, strtolower($moduleName), $slug, ucfirst($moduleName) . ' — Aide');
                $docs[$slug] = $meta;
            }

            uasort($docs, function (array $left, array $right): int {
                return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
            });

            return $docs;
        });
    }

    /**
     * Return indexed docs for a specific module slug.
     *
     * @return array<string, array<string, mixed>>
     */
    public function forModule(string $module): array
    {
        return array_filter(
            $this->index(),
            fn (array $doc) => ($doc['module'] ?? null) === $module
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, array<string, mixed>>
     */
    public function filteredIndex(array $filters = []): array
    {
        $module = trim((string) ($filters['module'] ?? ''));
        $version = trim((string) ($filters['version'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));

        return array_filter($this->index(), function (array $doc) use ($module, $version, $status, $category): bool {
            if ($module !== '' && (string) ($doc['module'] ?? '') !== $module) {
                return false;
            }
            if ($version !== '' && (string) ($doc['version'] ?? '') !== $version) {
                return false;
            }
            if ($status !== '' && (string) ($doc['status'] ?? '') !== $status) {
                return false;
            }
            if ($category !== '' && (string) ($doc['category'] ?? '') !== $category) {
                return false;
            }

            return true;
        });
    }

    /**
     * Load and parse a single doc by slug.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        $all = $this->index();

        if (!isset($all[$slug])) {
            return null;
        }

        $meta = $all[$slug];
        $markdown = file_get_contents($meta['path']);

        if ($markdown === false) {
            return null;
        }

        $content = $this->stripFrontMatter($markdown);
        $htmlCacheKey = 'docs:html:' . md5($meta['path'] . '|' . (string) filemtime($meta['path']));
        $html = Cache::remember($htmlCacheKey, now()->addMinutes(30), fn (): string => $this->toHtml($content));

        return array_merge($meta, [
            'html' => $html,
            'markdown' => $content,
            'related_docs' => $this->relatedDocs($slug, 5),
        ]);
    }

    /**
     * Search docs by keyword in title and content.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, array $filters = []): array
    {
        if (trim($query) === '') {
            return [];
        }

        $results = [];
        $terms = collect(preg_split('/\s+/', mb_strtolower(trim($query))) ?: [])
            ->map(fn ($term) => trim((string) $term))
            ->filter(fn ($term) => mb_strlen($term) >= 2)
            ->values()
            ->all();

        if ($terms === []) {
            return [];
        }

        foreach ($this->filteredIndex($filters) as $doc) {
            $content = (string) ($doc['markdown'] ?? '');
            $searchIndex = mb_strtolower((string) ($doc['search_index'] ?? ''));
            $lowerTitle = mb_strtolower($doc['title']);
            $score = 0;
            $firstTerm = null;

            foreach ($terms as $term) {
                $titleHits = substr_count($lowerTitle, $term);
                $contentHits = substr_count($searchIndex, $term);
                $tagHits = substr_count(mb_strtolower(implode(' ', (array) ($doc['tags'] ?? []))), $term);
                $categoryHits = substr_count(mb_strtolower((string) ($doc['category'] ?? '')), $term);

                if ($titleHits > 0) {
                    $score += $titleHits * 10;
                }
                if ($contentHits > 0) {
                    $score += $contentHits;
                }
                if ($tagHits > 0) {
                    $score += $tagHits * 5;
                }
                if ($categoryHits > 0) {
                    $score += $categoryHits * 4;
                }

                if ($firstTerm === null && ($titleHits > 0 || $contentHits > 0)) {
                    $firstTerm = $term;
                }
            }

            if ($score > 0) {
                $excerpt = $this->buildSearchExcerpt($content, mb_strtolower($content), (string) ($firstTerm ?? $terms[0]));

                $results[] = [
                    'slug'    => $doc['slug'],
                    'title'   => $doc['title'],
                    'excerpt' => trim($excerpt),
                    'module'  => $doc['module'],
                    'version' => $doc['version'],
                    'status' => $doc['status'],
                    'category' => $doc['category'],
                    'score'   => $score,
                ];
            }
        }

        usort($results, fn (array $a, array $b): int => (int) ($b['score'] ?? 0) <=> (int) ($a['score'] ?? 0));

        return array_map(function (array $row): array {
            unset($row['score']);

            return $row;
        }, $results);
    }

    /**
     * @return array{ok:bool, error:string|null}
     */
    public function publishToDiscord(array $doc): array
    {
        $enabled = filter_var((string) SettingService::get('docs.discord_publish_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        $webhookUrl = trim((string) SettingService::get('docs.discord_webhook_url', ''));
        if (!$enabled) {
            return ['ok' => false, 'error' => 'Publication Discord desactivee.'];
        }

        if ($webhookUrl === '') {
            return ['ok' => false, 'error' => 'Webhook Discord non configure.'];
        }

        $username = trim((string) SettingService::get('docs.discord_username', 'CATMIN Docs'));
        $excerpt = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($doc['html'] ?? ''))));
        $excerpt = Str::limit($excerpt, 350, '...');

        $payload = [
            'username' => $username !== '' ? $username : 'CATMIN Docs',
            'content' => 'Nouvelle publication documentation: **' . (string) ($doc['title'] ?? 'Documentation') . '**',
            'embeds' => [
                [
                    'title' => (string) ($doc['title'] ?? 'Documentation'),
                    'description' => trim(((string) ($doc['summary'] ?? '')) !== '' ? (string) $doc['summary'] : $excerpt),
                    'color' => 3447003,
                    'fields' => [
                        [
                            'name' => 'Module',
                            'value' => (string) ($doc['module'] ? ucfirst((string) $doc['module']) : 'General'),
                            'inline' => true,
                        ],
                        [
                            'name' => 'Slug',
                            'value' => (string) ($doc['slug'] ?? '-'),
                            'inline' => true,
                        ],
                        [
                            'name' => 'Version',
                            'value' => (string) ($doc['version'] ?? self::DEFAULT_VERSION),
                            'inline' => true,
                        ],
                        [
                            'name' => 'Categorie',
                            'value' => (string) ($doc['category'] ?? 'general'),
                            'inline' => true,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);
            if ($response->successful()) {
                return ['ok' => true, 'error' => null];
            }

            return ['ok' => false, 'error' => 'Discord HTTP ' . $response->status() . '.'];
        } catch (\Throwable $throwable) {
            return ['ok' => false, 'error' => Str::limit($throwable->getMessage(), 240, '')];
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function filterOptions(): array
    {
        $docs = collect($this->index());

        return [
            'modules' => $docs->pluck('module')->filter()->unique()->sort()->values()->all(),
            'versions' => $docs->pluck('version')->filter()->unique()->sort()->values()->all(),
            'statuses' => $docs->pluck('status')->filter()->unique()->sort()->values()->all(),
            'categories' => $docs->pluck('category')->filter()->unique()->sort()->values()->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function relatedDocs(string $slug, int $limit = 4): array
    {
        $docs = $this->index();
        $current = $docs[$slug] ?? null;
        if ($current === null) {
            return [];
        }

        $related = collect($docs)
            ->reject(fn (array $doc) => $doc['slug'] === $slug)
            ->map(function (array $doc) use ($current): array {
                $score = 0;
                if (($current['module'] ?? null) !== null && $doc['module'] === $current['module']) {
                    $score += 5;
                }
                if (($current['version'] ?? '') !== '' && $doc['version'] === $current['version']) {
                    $score += 3;
                }
                if (($current['category'] ?? '') !== '' && $doc['category'] === $current['category']) {
                    $score += 2;
                }
                $sharedTags = array_intersect((array) ($current['tags'] ?? []), (array) ($doc['tags'] ?? []));
                $score += count($sharedTags) * 2;

                $doc['score'] = $score;
                return $doc;
            })
            ->filter(fn (array $doc) => (int) ($doc['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->take(max(1, $limit))
            ->map(function (array $doc): array {
                unset($doc['score']);
                return $doc;
            })
            ->values()
            ->all();

        return $related;
    }

    private function buildSearchExcerpt(string $content, string $lowerContent, string $term): string
    {
        $pos = strpos($lowerContent, $term);

        if ($pos === false) {
            return Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($content))), 180, '...');
        }

        $start = max(0, $pos - 80);
        $excerpt = '...' . substr($content, $start, 220) . '...';

        return (string) preg_replace('/\s+/', ' ', strip_tags($excerpt));
    }

    /**
     * @param array<string, array<string, mixed>> $docs
     */
    private function scanDirectory(string $dir, ?string $module, array &$docs): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.md') ?: [] as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $slug = ($module ? $module . '-' : '') . strtolower(str_replace([' ', '_'], '-', $filename));
            $title = $this->extractTitle($file) ?? str_replace(['-', '_'], ' ', $filename);

            $docs[$slug] = $this->readMetadata($file, $module, $slug, $title);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readMetadata(string $filePath, ?string $module, string $slug, string $fallbackTitle): array
    {
        $markdown = file_get_contents($filePath);
        $markdown = $markdown === false ? '' : $markdown;
        $frontMatter = $this->parseFrontMatter($markdown);
        $content = $this->stripFrontMatter($markdown);
        $version = (string) ($frontMatter['version'] ?? $this->inferVersion($filePath, $content));
        $title = trim((string) ($frontMatter['title'] ?? $fallbackTitle));
        $category = trim((string) ($frontMatter['category'] ?? ($module !== null ? $module : 'general')));
        $status = trim((string) ($frontMatter['status'] ?? 'current'));
        $tags = $this->normalizeTagList($frontMatter['tags'] ?? []);
        $summary = trim((string) ($frontMatter['summary'] ?? ''));

        return [
            'slug' => $slug,
            'title' => $title !== '' ? $title : $fallbackTitle,
            'path' => $filePath,
            'module' => $module,
            'version' => $version !== '' ? $version : self::DEFAULT_VERSION,
            'status' => $status !== '' ? $status : 'current',
            'category' => $category !== '' ? $category : 'general',
            'tags' => $tags,
            'summary' => $summary,
            'markdown' => $content,
            'search_index' => $this->buildSearchIndex($title, $slug, $module, $version, $status, $category, $tags, $content),
            'updated_at' => @filemtime($filePath) ?: null,
        ];
    }

    private function extractTitle(string $filePath): ?string
    {
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return null;
        }

        $title = null;
        $linesRead = 0;

        while (($line = fgets($handle)) !== false && $linesRead < 20) {
            $line = trim($line);

            if (str_starts_with($line, '# ')) {
                $title = ltrim($line, '# ');
                break;
            }

            $linesRead++;
        }

        fclose($handle);

        return $title ?: null;
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function parseFrontMatter(string $markdown): array
    {
        if (!preg_match('/\A---\R(.*?)\R---\R/s', $markdown, $matches)) {
            return [];
        }

        $result = [];
        foreach (preg_split('/\R/', (string) $matches[1]) ?: [] as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            if ($key === '') {
                continue;
            }

            if ($key === 'tags') {
                $result[$key] = $this->normalizeTagList($value);
                continue;
            }

            $result[$key] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return $result;
    }

    private function stripFrontMatter(string $markdown): string
    {
        return (string) preg_replace('/\A---\R.*?\R---\R/s', '', $markdown, 1);
    }

    /**
     * @param string|array<int, string> $tags
     * @return array<int, string>
     */
    private function normalizeTagList(string|array $tags): array
    {
        if (is_array($tags)) {
            return collect($tags)->map(fn (mixed $tag): string => trim((string) $tag))->filter()->values()->all();
        }

        return collect(explode(',', $tags))->map(fn (string $tag): string => trim($tag))->filter()->values()->all();
    }

    private function inferVersion(string $filePath, string $content): string
    {
        $haystack = $filePath . ' ' . $content;
        if (preg_match('/V(\d+(?:\.\d+)?(?:-dev)?)/i', $haystack, $matches)) {
            return 'V' . $matches[1];
        }

        return self::DEFAULT_VERSION;
    }

    /**
     * @param array<int, string> $tags
     */
    private function buildSearchIndex(string $title, string $slug, ?string $module, string $version, string $status, string $category, array $tags, string $content): string
    {
        return mb_strtolower(implode(' ', array_filter([
            $title,
            $slug,
            $module,
            $version,
            $status,
            $category,
            implode(' ', $tags),
            $content,
        ])));
    }

    private function indexCacheKey(): string
    {
        $files = array_merge(
            glob($this->docsPath() . '/*.md') ?: [],
            glob($this->modulesPath() . '/*/docs/*.md') ?: [],
            glob($this->modulesPath() . '/*/HELP.md') ?: [],
        );

        sort($files);

        $signature = collect($files)
            ->map(fn (string $file): string => $file . ':' . ((string) (@filemtime($file) ?: 0)))
            ->implode('|');

        return 'docs:index:' . md5($signature);
    }
}
