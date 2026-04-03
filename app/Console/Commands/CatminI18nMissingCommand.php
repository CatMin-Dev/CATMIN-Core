<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * catmin:i18n:missing — compare translation files across locales to surface missing keys.
 *
 * Compares each lang/{locale}/{file}.php against the reference locale (default: fr)
 * and reports keys missing in other locales.
 */
class CatminI18nMissingCommand extends Command
{
    protected $signature = 'catmin:i18n:missing
                            {--reference=fr : Reference locale to compare against}
                            {--locale= : Target locale to check (all supported if omitted)}
                            {--path= : Path to lang directory (defaults to lang/)}';

    protected $description = 'Afficher les clés de traduction manquantes entre les locales supportées.';

    public function handle(): int
    {
        $langPath  = $this->option('path') ? base_path((string) $this->option('path')) : base_path('lang');
        $reference = (string) ($this->option('reference') ?? 'fr');
        $targetOpt = $this->option('locale') ? [(string) $this->option('locale')] : null;

        if (!is_dir($langPath)) {
            $this->error("Dossier lang introuvable : {$langPath}");
            return self::FAILURE;
        }

        // Discover locales
        $locales = collect(File::directories($langPath))
            ->map(fn ($d) => basename($d))
            ->sort()
            ->values()
            ->toArray();

        if (!in_array($reference, $locales, true)) {
            $this->error("Locale de référence '{$reference}' introuvable dans {$langPath}");
            return self::FAILURE;
        }

        $targets = $targetOpt
            ? array_filter($locales, fn ($l) => in_array($l, $targetOpt, true))
            : array_filter($locales, fn ($l) => $l !== $reference);

        $refDir   = $langPath . '/' . $reference;
        $refFiles = File::files($refDir);
        $missing  = [];
        $total    = 0;

        foreach ($refFiles as $refFile) {
            $fileName  = $refFile->getFilename();
            $refKeys   = $this->flattenKeys((array) include $refFile->getPathname());

            foreach ($targets as $target) {
                $targetFile = $langPath . '/' . $target . '/' . $fileName;

                if (!file_exists($targetFile)) {
                    // Entire file missing
                    foreach ($refKeys as $key) {
                        $missing[] = [$target, $fileName, $key, '(fichier manquant)'];
                        $total++;
                    }
                    continue;
                }

                $targetKeys = $this->flattenKeys((array) include $targetFile);
                $diff       = array_diff($refKeys, $targetKeys);

                foreach ($diff as $key) {
                    $missing[] = [$target, $fileName, $key, ''];
                    $total++;
                }
            }
        }

        if ($total === 0) {
            $this->info("Aucune clé manquante. Toutes les locales sont synchronisées.");
            return self::SUCCESS;
        }

        $this->warn("{$total} clé(s) manquante(s) trouvée(s) :");
        $this->newLine();
        $this->table(['Locale', 'Fichier', 'Clé manquante', 'Note'], $missing);

        return self::SUCCESS;
    }

    /**
     * Flatten a nested translation array into dot-notation keys.
     *
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
}
