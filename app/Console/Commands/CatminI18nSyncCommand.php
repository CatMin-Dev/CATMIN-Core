<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * catmin:i18n:sync — generate stub entries for missing translation keys.
 *
 * Reads the reference locale files and appends (as commented stubs) any keys
 * that are absent in target locale files, so translators can fill them in.
 *
 * DRY RUN by default — use --write to actually modify files.
 */
class CatminI18nSyncCommand extends Command
{
    protected $signature = 'catmin:i18n:sync
                            {--reference=fr : Reference locale}
                            {--locale= : Target locale to sync (all non-reference if omitted)}
                            {--write : Actually write stub entries to target files}
                            {--path= : Path to lang directory}';

    protected $description = 'Synchroniser les fichiers de traduction en ajoutant des stubs pour les clés manquantes.';

    public function handle(): int
    {
        $langPath  = $this->option('path') ? base_path((string) $this->option('path')) : base_path('lang');
        $reference = (string) ($this->option('reference') ?? 'fr');
        $write     = (bool) $this->option('write');
        $targetOpt = $this->option('locale') ? [(string) $this->option('locale')] : null;

        if (!is_dir($langPath)) {
            $this->error("Dossier lang introuvable : {$langPath}");
            return self::FAILURE;
        }

        $locales = collect(File::directories($langPath))
            ->map(fn ($d) => basename($d))
            ->sort()
            ->values()
            ->toArray();

        $targets = $targetOpt
            ? array_filter($locales, fn ($l) => in_array($l, $targetOpt, true))
            : array_filter($locales, fn ($l) => $l !== $reference);

        $refDir   = $langPath . '/' . $reference;
        $refFiles = File::files($refDir);
        $totalAdded = 0;

        foreach ($refFiles as $refFile) {
            $fileName = $refFile->getFilename();

            foreach ($targets as $target) {
                $targetDir  = $langPath . '/' . $target;
                $targetFile = $targetDir . '/' . $fileName;

                // Load reference keys
                $refArray  = (array) include $refFile->getPathname();
                $refFlat   = $this->flattenKeys($refArray);

                if (!file_exists($targetFile)) {
                    $existingFlat = [];
                    $targetArray  = [];
                } else {
                    $targetArray  = (array) include $targetFile;
                    $existingFlat = $this->flattenKeys($targetArray);
                }

                $diff = array_diff($refFlat, $existingFlat);
                if (empty($diff)) {
                    continue;
                }

                $this->info("[{$target}/{$fileName}] " . count($diff) . " clé(s) à ajouter.");

                if ($write) {
                    $stubs = PHP_EOL . PHP_EOL . '    // --- À TRADUIRE ---';
                    foreach ($diff as $key) {
                        $refValue = $this->getNestedValue($refArray, $key);
                        $escaped  = addslashes((string) $refValue);
                        $stubs   .= PHP_EOL . "    // '{$key}' => '{$escaped}',";
                    }

                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    if (!file_exists($targetFile)) {
                        // Create a new file skeleton
                        $content = "<?php\n\nreturn [\n{$stubs}\n];\n";
                    } else {
                        // Append stubs before the closing ];
                        $content = (string) File::get($targetFile);
                        $content = preg_replace('/\];?\s*$/', $stubs . PHP_EOL . '];', $content);
                    }

                    File::put($targetFile, $content);
                    $this->line("  → Écrit dans {$target}/{$fileName}");
                }

                $totalAdded += count($diff);
            }
        }

        if ($totalAdded === 0) {
            $this->info('Tout est synchronisé, rien à faire.');
        } else {
            $mode = $write ? 'ajoutées' : 'à ajouter (dry-run, utilisez --write pour écrire)';
            $this->info("{$totalAdded} entrée(s) {$mode}.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed> $array
     * @return array<int, string>
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $k => $v) {
            $fullKey = $prefix === '' ? (string) $k : $prefix . '.' . $k;
            if (is_array($v)) {
                $keys = array_merge($keys, $this->flattenKeys($v, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }
        return $keys;
    }

    private function getNestedValue(array $array, string $dotKey): mixed
    {
        $parts = explode('.', $dotKey);
        $current = $array;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $dotKey;
            }
            $current = $current[$part];
        }
        return $current;
    }
}
