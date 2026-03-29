<?php

namespace Modules\Docs\Services;

use App\Services\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class DocsService
{
    /**
     * Base directory for global documentation files.
     */
    public function docsPath(): string
    {
        return base_path('docs-site');
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
     * @return array<string, array{slug: string, title: string, path: string, module: string|null}>
     */
    public function index(): array
    {
        $docs = [];

        // Global docs-site docs
        $this->scanDirectory($this->docsPath(), null, $docs);

        // Per-module HELP.md files
        $modulesPath = base_path('modules');
        foreach (glob($modulesPath . '/*/docs') ?: [] as $modDocsDir) {
            $moduleName = basename(dirname($modDocsDir));
            $this->scanDirectory($modDocsDir, strtolower($moduleName), $docs);
        }
        foreach (glob($modulesPath . '/*/HELP.md') ?: [] as $helpFile) {
            $moduleName = basename(dirname($helpFile));
            $slug = 'help-' . strtolower($moduleName);
            $docs[$slug] = [
                'slug'   => $slug,
                'title'  => ucfirst($moduleName) . ' — Aide',
                'path'   => $helpFile,
                'module' => strtolower($moduleName),
            ];
        }

        ksort($docs);

        return $docs;
    }

    /**
     * Return indexed docs for a specific module slug.
     *
     * @return array<string, array{slug: string, title: string, path: string, module: string|null}>
     */
    public function forModule(string $module): array
    {
        return array_filter(
            $this->index(),
            fn (array $doc) => ($doc['module'] ?? null) === $module
        );
    }

    /**
     * Load and parse a single doc by slug.
     *
     * @return array{slug: string, title: string, html: string, module: string|null}|null
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

        return [
            'slug'   => $slug,
            'title'  => $meta['title'],
            'html'   => $this->toHtml($markdown),
            'module' => $meta['module'],
        ];
    }

    /**
     * Search docs by keyword in title and content.
     *
     * @return array<int, array{slug: string, title: string, excerpt: string, module: string|null}>
     */
    public function search(string $query): array
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

        foreach ($this->index() as $doc) {
            $content = file_get_contents($doc['path']);

            if ($content === false) {
                continue;
            }

            $lowerContent = mb_strtolower($content);
            $lowerTitle = mb_strtolower($doc['title']);
            $score = 0;
            $firstTerm = null;

            foreach ($terms as $term) {
                $titleHits = substr_count($lowerTitle, $term);
                $contentHits = substr_count($lowerContent, $term);

                if ($titleHits > 0) {
                    $score += $titleHits * 10;
                }
                if ($contentHits > 0) {
                    $score += $contentHits;
                }

                if ($firstTerm === null && ($titleHits > 0 || $contentHits > 0)) {
                    $firstTerm = $term;
                }
            }

            if ($score > 0) {
                $excerpt = $this->buildSearchExcerpt($content, $lowerContent, (string) ($firstTerm ?? $terms[0]));

                $results[] = [
                    'slug'    => $doc['slug'],
                    'title'   => $doc['title'],
                    'excerpt' => trim($excerpt),
                    'module'  => $doc['module'],
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
                    'description' => $excerpt,
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
     * @param array<string, array{slug: string, title: string, path: string, module: string|null}> $docs
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

            $docs[$slug] = [
                'slug'   => $slug,
                'title'  => $title,
                'path'   => $file,
                'module' => $module,
            ];
        }
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
}
