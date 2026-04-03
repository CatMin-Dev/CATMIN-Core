<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * catmin:i18n:scan — scan views and PHP files for translation key usage.
 *
 * Finds all __('file.key') / __('namespace::file.key') / @lang('...') calls
 * and outputs a report of unique keys found, grouped by file.
 */
class CatminI18nScanCommand extends Command
{
    protected $signature = 'catmin:i18n:scan
                            {--path= : Restrict scan to a specific directory (relative to base_path)}
                            {--format=table : Output format: table or json}';

    protected $description = 'Scanner les fichiers PHP et Blade pour les clés de traduction utilisées.';

    public function handle(): int
    {
        $basePath  = base_path();
        $scanPath  = $this->option('path') ? base_path((string) $this->option('path')) : null;
        $format    = (string) ($this->option('format') ?? 'table');

        $directories = $scanPath
            ? [$scanPath]
            : [
                base_path('app'),
                base_path('resources/views'),
                base_path('modules'),
                base_path('addons'),
              ];

        $pattern = '/(?:__|trans|@lang)\s*\(\s*[\'"]([a-zA-Z0-9_\-:\.]+)[\'"]/';
        $results  = [];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = File::allFiles($directory);

            foreach ($files as $file) {
                $ext = $file->getExtension();
                if (!in_array($ext, ['php', 'blade'], true) && $file->getFilename() !== 'html') {
                    if (!str_ends_with($file->getFilename(), '.blade.php')) {
                        continue;
                    }
                }

                $content = (string) File::get($file->getPathname());

                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $key) {
                        $relPath = str_replace($basePath . '/', '', $file->getPathname());
                        $results[$key][] = $relPath;
                    }
                }
            }
        }

        ksort($results);

        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->info('Clés de traduction détectées : ' . count($results));
        $this->newLine();

        $rows = [];
        foreach ($results as $key => $files) {
            $rows[] = [$key, count(array_unique($files)), implode("\n", array_unique($files))];
        }

        $this->table(['Clé', 'Fichiers', 'Chemins'], $rows);

        return self::SUCCESS;
    }
}
