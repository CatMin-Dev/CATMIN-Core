<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

require_once CATMIN_CORE . '/updater-github.php';
require_once CATMIN_CORE . '/updater-validator.php';
require_once CATMIN_CORE . '/db-upgrade-runner.php';
require_once CATMIN_CORE . '/logs/Logger.php';

final class CoreUpdaterRunner
{
    private string $downloadsDir;
    private string $stagingDir;
    private string $backupsDir;
    private string $reportsDir;

    public function __construct()
    {
        $this->downloadsDir = CATMIN_STORAGE . '/updates/downloads';
        $this->stagingDir = CATMIN_STORAGE . '/updates/staging';
        $this->backupsDir = CATMIN_STORAGE . '/updates/backups';
        $this->reportsDir = CATMIN_STORAGE . '/updates/reports';
    }

    public function check(): array
    {
        $this->ensureDirs();
        $local = $this->localVersion();
        $github = new CoreUpdaterGithub();
        $latestRelease = $github->latestRelease();
        $latestTag = $github->latestTag();

        if (!($latestRelease['ok'] ?? false) && !($latestTag['ok'] ?? false)) {
            return [
                'ok' => false,
                'local_version' => $local,
                'update_available' => false,
                'error' => (string) ($latestRelease['error'] ?? $latestTag['error'] ?? 'Erreur update inconnue.'),
            ];
        }

        $release = is_array($latestRelease['release'] ?? null) ? $latestRelease['release'] : [];
        $releaseTag = $this->normalizeTag((string) ($release['tag'] ?? ''));
        $tagFallback = $this->normalizeTag((string) ($latestTag['tag'] ?? ''));
        $tag = $releaseTag;
        if ($tag === '' || ($tagFallback !== '' && version_compare($tagFallback, $tag, '>'))) {
            $tag = $tagFallback;
        }

        $asset = $github->findStandaloneAsset($release);

        $updateAvailable = $tag !== '' && version_compare($tag, $local, '>');
        $updateRunnable = $releaseTag !== ''
            && version_compare($releaseTag, $local, '>')
            && is_array($asset)
            && trim((string) ($asset['url'] ?? '')) !== '';

        return [
            'ok' => true,
            'local_version' => $local,
            'remote_version' => $tag,
            'remote_release_version' => $releaseTag,
            'update_available' => $updateAvailable,
            'update_runnable' => $updateRunnable,
            'release' => $release,
            'asset' => $asset,
            'error' => '',
        ];
    }

    public function run(bool $dryRun = true): array
    {
        $started = date('c');
        $check = $this->check();
        $report = [
            'started_at' => $started,
            'dry_run' => $dryRun,
            'check' => $check,
            'steps' => [],
            'ok' => false,
            'error' => '',
        ];

        if (!($check['ok'] ?? false)) {
            $report['error'] = (string) ($check['error'] ?? 'Check update KO.');
            return $this->writeReport($report);
        }

        if (!($check['update_available'] ?? false)) {
            $report['ok'] = true;
            $report['steps'][] = ['name' => 'check', 'status' => 'noop', 'message' => 'Aucune update disponible.'];
            return $this->writeReport($report);
        }

        $asset = is_array($check['asset'] ?? null) ? $check['asset'] : null;
        if (!is_array($asset) || trim((string) ($asset['url'] ?? '')) === '') {
            $report['error'] = 'Asset ZIP standalone introuvable.';
            return $this->writeReport($report);
        }

        $stamp = date('Ymd-His');
        $backup = $this->createBackup($stamp);
        $report['steps'][] = ['name' => 'backup', 'status' => ($backup['ok'] ? 'ok' : 'error'), 'message' => (string) ($backup['message'] ?? '')];
        if (!($backup['ok'] ?? false)) {
            $report['error'] = (string) ($backup['message'] ?? 'Backup KO.');
            return $this->writeReport($report);
        }

        $maint = $this->setMaintenance(true, 'Core update in progress');
        $report['steps'][] = ['name' => 'maintenance_on', 'status' => ($maint['ok'] ? 'ok' : 'warning'), 'message' => $maint['message']];

        try {
            $downloadPath = $this->downloadsDir . '/core-update-' . $stamp . '.zip';
            $dl = (new CoreUpdaterGithub())->downloadAsset((string) $asset['url'], $downloadPath);
            $report['steps'][] = ['name' => 'download', 'status' => ($dl['ok'] ? 'ok' : 'error'), 'message' => (string) ($dl['error'] ?? '')];
            if (!($dl['ok'] ?? false)) {
                $report['error'] = (string) ($dl['error'] ?? 'Téléchargement KO.');
                return $this->writeReport($report);
            }

            $validation = (new CoreUpdaterValidator())->validateZip($downloadPath);
            $report['steps'][] = ['name' => 'validate_zip', 'status' => ($validation['ok'] ? 'ok' : 'error'), 'message' => implode(' | ', (array) ($validation['errors'] ?? []))];
            if (!($validation['ok'] ?? false)) {
                $report['error'] = implode(' | ', (array) ($validation['errors'] ?? ['ZIP invalide.']));
                return $this->writeReport($report);
            }

            $stagingPath = $this->stagingDir . '/core-update-' . $stamp;
            if (!is_dir($stagingPath) && !@mkdir($stagingPath, 0775, true) && !is_dir($stagingPath)) {
                $report['error'] = 'Création staging impossible.';
                return $this->writeReport($report);
            }

            $extract = $this->extractZip($downloadPath, $stagingPath);
            $report['steps'][] = ['name' => 'extract', 'status' => ($extract['ok'] ? 'ok' : 'error'), 'message' => (string) ($extract['message'] ?? '')];
            if (!($extract['ok'] ?? false)) {
                $report['error'] = (string) ($extract['message'] ?? 'Extraction KO.');
                return $this->writeReport($report);
            }

            $sourceRoot = $this->detectPackageRoot($stagingPath);
            if ($sourceRoot === null) {
                $report['error'] = 'Package root introuvable en staging.';
                return $this->writeReport($report);
            }

            if ($dryRun) {
                $planned = $this->countFiles($sourceRoot);
                $report['steps'][] = ['name' => 'plan', 'status' => 'ok', 'message' => 'Fichiers planifiés: ' . $planned];
                $report['ok'] = true;
                return $this->writeReport($report);
            }

            $apply = $this->applyPackage($sourceRoot, CATMIN_ROOT);
            $report['steps'][] = ['name' => 'apply', 'status' => ($apply['ok'] ? 'ok' : 'error'), 'message' => (string) ($apply['message'] ?? '')];
            if (!($apply['ok'] ?? false)) {
                $report['error'] = (string) ($apply['message'] ?? 'Apply KO.');
                return $this->writeReport($report);
            }

            $upgrade = (new CoreDbUpgradeRunner())->run();
            $report['steps'][] = ['name' => 'migrations', 'status' => (($upgrade['ok'] ?? false) ? 'ok' : 'warning'), 'message' => (string) ($upgrade['message'] ?? '')];

            $report['ok'] = true;
            return $this->writeReport($report);
        } finally {
            $maintOff = $this->setMaintenance(false, 'Core update done');
            $report['steps'][] = ['name' => 'maintenance_off', 'status' => ($maintOff['ok'] ? 'ok' : 'warning'), 'message' => $maintOff['message']];
        }
    }

    private function ensureDirs(): void
    {
        foreach ([$this->downloadsDir, $this->stagingDir, $this->backupsDir, $this->reportsDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    private function localVersion(): string
    {
        $file = CATMIN_ROOT . '/version.json';
        if (!is_file($file)) {
            return '0.0.0-dev.0';
        }
        $decoded = json_decode((string) file_get_contents($file), true);
        return trim((string) ($decoded['version'] ?? '0.0.0-dev.0')) ?: '0.0.0-dev.0';
    }

    private function normalizeTag(string $tag): string
    {
        $tag = trim($tag);
        if (str_starts_with(strtolower($tag), 'v')) {
            $tag = substr($tag, 1);
        }
        return $tag;
    }

    private function createBackup(string $stamp): array
    {
        $this->ensureDirs();
        $zipPath = $this->backupsDir . '/core-pre-update-' . $stamp . '.zip';
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'Extension zip requise pour backup update.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['ok' => false, 'message' => 'Création ZIP backup impossible.'];
        }

        $include = [
            CATMIN_ROOT . '/version.json',
            CATMIN_ROOT . '/.env.example',
            CATMIN_ROOT . '/db/database.sqlite',
            CATMIN_STORAGE . '/config/runtime.json',
        ];

        foreach ($include as $file) {
            if (is_file($file)) {
                $relative = ltrim(str_replace(CATMIN_ROOT, '', $file), '/');
                $zip->addFile($file, $relative);
            }
        }

        $zip->close();

        return [
            'ok' => is_file($zipPath),
            'message' => is_file($zipPath) ? ('Backup créé: ' . basename($zipPath)) : 'Backup KO',
            'path' => $zipPath,
        ];
    }

    private function setMaintenance(bool $enabled, string $reason): array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'settings';
            $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (category, setting_key, setting_value, is_encrypted, created_at, updated_at) VALUES (:category, :k, :v, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP');
            $ok = $stmt->execute(['category' => 'maintenance', 'k' => 'enabled', 'v' => $enabled ? '1' : '0']);
            $stmt->execute(['category' => 'maintenance', 'k' => 'reason', 'v' => $reason]);
            return ['ok' => $ok, 'message' => $enabled ? 'Maintenance activée.' : 'Maintenance désactivée.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Maintenance DB non modifiée: ' . $e->getMessage()];
        }
    }

    private function extractZip(string $zipPath, string $destination): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'ZIP non ouvrable.'];
        }
        $ok = $zip->extractTo($destination);
        $zip->close();
        return ['ok' => $ok, 'message' => $ok ? 'Extraction OK.' : 'Extraction KO.'];
    }

    private function detectPackageRoot(string $stagingPath): ?string
    {
        $candidate = $stagingPath . '/version.json';
        if (is_file($candidate)) {
            return $stagingPath;
        }

        foreach (glob($stagingPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (is_file($dir . '/version.json')) {
                return $dir;
            }
        }

        return null;
    }

    private function applyPackage(string $sourceRoot, string $targetRoot): array
    {
        $skipRoots = [
            'storage/backups',
            'storage/logs',
            'storage/cache',
            'storage/sessions',
            'storage/tmp',
            'storage/updates',
            'db/database.sqlite',
            '.env',
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relative = ltrim(str_replace($sourceRoot, '', $sourcePath), '/');
            if ($relative === '') {
                continue;
            }

            $skip = false;
            foreach ($skipRoots as $prefix) {
                if ($relative === $prefix || str_starts_with($relative, $prefix . '/')) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            $targetPath = $targetRoot . '/' . $relative;
            if ($item->isDir()) {
                if (!is_dir($targetPath) && !@mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    return ['ok' => false, 'message' => 'Création dossier KO: ' . $relative];
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                return ['ok' => false, 'message' => 'Création dossier parent KO: ' . $relative];
            }
            if (!@copy($sourcePath, $targetPath)) {
                return ['ok' => false, 'message' => 'Copie KO: ' . $relative];
            }
        }

        return ['ok' => true, 'message' => 'Update appliquée (overwrite contrôlé).'];
    }

    private function countFiles(string $root): int
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
            }
        }
        return $count;
    }

    private function writeReport(array $report): array
    {
        $this->ensureDirs();
        $report['finished_at'] = date('c');
        $file = $this->reportsDir . '/github-update-' . date('Ymd-His') . '.json';
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($json)) {
            @file_put_contents($file, $json . PHP_EOL, LOCK_EX);
            @file_put_contents($this->reportsDir . '/latest-github-update.json', $json . PHP_EOL, LOCK_EX);
        }
        return $report;
    }
}
