<?php

namespace Modules\Docs\Services;

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
        $lowerQuery = mb_strtolower($query);

        foreach ($this->index() as $doc) {
            $content = file_get_contents($doc['path']);

            if ($content === false) {
                continue;
            }

            $lowerContent = mb_strtolower($content);
            $lowerTitle = mb_strtolower($doc['title']);

            if (str_contains($lowerTitle, $lowerQuery) || str_contains($lowerContent, $lowerQuery)) {
                // Find excerpt around first match
                $pos = strpos($lowerContent, $lowerQuery);
                $start = max(0, $pos - 80);
                $excerpt = '...' . substr($content, $start, 200) . '...';
                $excerpt = preg_replace('/\s+/', ' ', strip_tags($excerpt));

                $results[] = [
                    'slug'    => $doc['slug'],
                    'title'   => $doc['title'],
                    'excerpt' => trim($excerpt),
                    'module'  => $doc['module'],
                ];
            }
        }

        return $results;
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
