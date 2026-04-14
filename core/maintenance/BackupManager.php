<?php

declare(strict_types=1);

namespace Core\maintenance;

use Core\versioning\Version;
use PDO;
use Throwable;

final class BackupManager
{
    private const SUPPORTED_TYPES = [
        'db_only',
        'files_only',
        'db_files',
        'full_instance',
        'pre_update_snapshot',
        'pre_restore_snapshot',
    ];

    private const SUPPORTED_RESTORE_MODES = ['db_only', 'files_only', 'full'];

    private string $backupRoot;
    private string $lockPath;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $backupsTable,
        private readonly string $auditTable,
        private readonly string $backupFormatVersion = '0.1.0-RC.1'
    ) {
        $this->backupRoot = CATMIN_STORAGE . '/backups';
        $this->lockPath = CATMIN_STORAGE . '/locks/maintenance-backups.lock';
    }

    /** @return array<int, array<string, mixed>> */
    public function listBackups(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $rows = [];

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, backup_type, status, file_path, checksum, size_bytes, created_at, backup_format_version, core_version, origin, manifest, integrity_status, is_orphan, created_by_user_id, created_by_username, last_error '
                . 'FROM ' . $this->backupsTable . ' WHERE backup_type != :restore ORDER BY created_at DESC LIMIT :limit'
            );
            $stmt->bindValue(':restore', 'restore', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $path = (string) ($row['file_path'] ?? '');
            $real = is_file($path) ? (string) realpath($path) : '';
            $exists = $real !== '' && str_starts_with($real, $this->backupRoot . '/');
            $size = (int) ($row['size_bytes'] ?? 0);
            if ($size <= 0 && $exists) {
                $size = (int) (@filesize($real) ?: 0);
            }

            $manifest = $this->decodeManifest((string) ($row['manifest'] ?? ''));
            if ($manifest === []) {
                $manifest = $this->loadManifestFromBackup($real !== '' ? $real : $path);
            }

            $integrity = $this->checkIntegrity($real !== '' ? $real : $path, (string) ($row['checksum'] ?? ''));
            $state = $integrity['ok'] ? 'ok' : ($exists ? 'warning' : 'orphan');
            if ((int) ($row['is_orphan'] ?? 0) === 1) {
                $state = 'orphan';
            }

            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => basename($path !== '' ? $path : ('backup-' . (string) ($row['id'] ?? '0'))),
                'path' => $path,
                'exists' => $exists,
                'size' => $size,
                'date' => (string) ($row['created_at'] ?? ''),
                'type' => (string) ($row['backup_type'] ?? 'db_only'),
                'status' => (string) ($row['status'] ?? ''),
                'backup_format_version' => (string) ($row['backup_format_version'] ?? $this->backupFormatVersion),
                'core_version' => (string) ($row['core_version'] ?? ''),
                'origin' => (string) ($row['origin'] ?? 'manual'),
                'manifest' => $manifest,
                'integrity' => $state,
                'integrity_message' => (string) ($integrity['message'] ?? ''),
                'created_by_user_id' => (int) ($row['created_by_user_id'] ?? 0),
                'created_by_username' => (string) ($row['created_by_username'] ?? ''),
                'last_error' => (string) ($row['last_error'] ?? ''),
                'is_orphan' => $state === 'orphan',
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function createBackup(string $type, array $context = []): array
    {
        $type = strtolower(trim($type));
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            return ['ok' => false, 'message' => 'Type de sauvegarde invalide.'];
        }

        if (!is_dir($this->backupRoot) && !@mkdir($this->backupRoot, 0775, true) && !is_dir($this->backupRoot)) {
            return $this->registerBackupFailure($type, '', [], $context, 'Dossier de sauvegarde indisponible.');
        }

        $lock = $this->acquireLock();
        if ($lock === null) {
            return ['ok' => false, 'message' => 'Opération en cours. Réessaie dans quelques secondes.'];
        }

        try {
            $stamp = gmdate('Ymd_His');
            $filename = 'catmin-' . $type . '-' . $stamp . '.zip';
            $path = $this->backupRoot . '/' . $filename;

            $manifest = $this->buildManifest($type, $context);
            $sqlDump = '';
            if (in_array($type, ['db_only', 'db_files', 'full_instance', 'pre_update_snapshot', 'pre_restore_snapshot'], true)) {
                $sqlDump = $this->buildSqlDump();
                $manifest['content']['sql'] = [
                    'tables_count' => $this->countSqlTables($sqlDump),
                    'bytes' => strlen($sqlDump),
                    'included' => true,
                ];
            }

            $zipOk = false;
            $zipError = '';
            if (class_exists('ZipArchive')) {
                $this->prepareZipTemporaryDirectory();
                $zip = new \ZipArchive();
                $opened = $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                if ($opened === true) {
                    $zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    if ($sqlDump !== '') {
                        $zip->addFromString('db/dump.sql', $sqlDump);
                    }
                    $this->addContentByType($zip, $type, $manifest);
                    // ZipArchive::close can emit warnings when TMP is not writable: avoid breaking flow and fallback safely.
                    $zipOk = (bool) @($zip->close());
                    if (!$zipOk) {
                        $zipError = 'ZipArchive::close impossible (droits dossier temporaire).';
                    }
                } else {
                    $zipError = 'ZipArchive::open code=' . (string) $opened;
                }
            } else {
                $zipError = 'Extension ZipArchive indisponible.';
            }

            if (!$zipOk) {
                $fallback = $this->createSqlFallbackBackup($type, $stamp, $sqlDump, $manifest, $context);
                if (!((bool) ($fallback['ok'] ?? false))) {
                    return $this->registerBackupFailure($type, $path, $manifest, $context, 'Création archive impossible: ' . $zipError);
                }

                return $fallback;
            }

            $size = (int) (@filesize($path) ?: 0);
            $checksum = (string) (@hash_file('sha256', $path) ?: '');
            $manifest['file'] = ['name' => $filename, 'size' => $size, 'checksum_sha256' => $checksum];
            $this->injectManifestIntoZip($path, $manifest);

            $rowId = $this->insertBackupRow($type, $path, 'success', $checksum, $size, $context, $manifest);

            $this->logAudit(
                    'backup.create',
                'success',
                    'Sauvegarde créée',
                [
                    'backup_id' => $rowId,
                    'file' => $filename,
                    'type' => $type,
                    'size_bytes' => $size,
                ],
                $context
            );

            return [
                'ok' => true,
                'id' => $rowId,
                'name' => $filename,
                'path' => $path,
                'size' => $size,
                'checksum' => $checksum,
                'manifest' => $manifest,
                'message' => 'Sauvegarde créée: ' . $filename,
            ];
        } catch (Throwable $e) {
            return $this->registerBackupFailure($type, '', [], $context, 'Échec création sauvegarde: ' . substr($e->getMessage(), 0, 180));
        } finally {
            $this->releaseLock($lock);
        }
    }

    /** @return array<string, mixed> */
    public function readBackup(string $name): array
    {
        $entry = $this->findByName($name);
        if ($entry === null) {
            return ['ok' => false, 'message' => 'Sauvegarde introuvable.'];
        }

        $path = (string) ($entry['file_path'] ?? '');
        $real = is_file($path) ? (string) realpath($path) : '';
        if ($real === '' || !str_starts_with($real, $this->backupRoot . '/')) {
            return [
                'ok' => false,
                'message' => 'Sauvegarde absente du stockage.',
                'orphan' => true,
                'entry' => $entry,
            ];
        }

        $manifest = $this->decodeManifest((string) ($entry['manifest'] ?? ''));
        if ($manifest === []) {
            $manifest = $this->loadManifestFromBackup($real);
        }

        $details = $this->analyzeBackupContents($real, $manifest);

        return [
            'ok' => true,
            'name' => basename($real),
            'path' => $real,
            'size' => (int) (@filesize($real) ?: 0),
            'entry' => $entry,
            'manifest' => $manifest,
            'details' => $details,
            'preview_text' => $details['preview_text'] ?? '',
            'is_text_preview' => (bool) ($details['is_text_preview'] ?? false),
        ];
    }

    /** @return array<string, mixed> */
    public function deleteBackup(string $name, array $context = [], bool $repairOrphan = false): array
    {
        $entry = $this->findByName($name);
        if ($entry === null) {
            return ['ok' => false, 'message' => 'Sauvegarde introuvable en index.'];
        }

        $id = (int) ($entry['id'] ?? 0);
        $path = (string) ($entry['file_path'] ?? '');
        if ($id <= 0 || $path === '') {
            return ['ok' => false, 'message' => 'Entrée backup invalide.'];
        }

        $lock = $this->acquireLock();
        if ($lock === null) {
            return ['ok' => false, 'message' => 'Suppression concurrente détectée.'];
        }

        try {
            $real = is_file($path) ? (string) realpath($path) : '';
            $exists = $real !== '' && str_starts_with($real, $this->backupRoot . '/');

            $this->pdo->beginTransaction();

            if (!$exists) {
                $mark = $this->pdo->prepare('UPDATE ' . $this->backupsTable . ' SET is_orphan = 1, integrity_status = :status, last_error = :error WHERE id = :id');
                $mark->execute([
                    'status' => 'orphan',
                    'error' => 'Fichier backup manquant sur disque',
                    'id' => $id,
                ]);

                if (!$repairOrphan) {
                    $this->pdo->commit();
                    $this->logAudit('backup.delete', 'warning', 'Suppression refusee: backup orphelin', ['backup_id' => $id, 'file_path' => $path], $context);
                    return [
                        'ok' => false,
                        'message' => 'Le fichier est absent physiquement. Lance la réparation de l\'index.',
                        'orphan' => true,
                    ];
                }

                $delOrphan = $this->pdo->prepare('DELETE FROM ' . $this->backupsTable . ' WHERE id = :id');
                $delOrphan->execute(['id' => $id]);
                $this->pdo->commit();

                $this->logAudit('backup.orphan.repair', 'success', 'Entree orpheline retiree', ['backup_id' => $id, 'file_path' => $path], $context);
                return ['ok' => true, 'message' => 'Index réparé: entrée orpheline supprimée.'];
            }

            if (!@unlink($real)) {
                $this->pdo->rollBack();
                $this->logAudit('backup.delete', 'error', 'Suppression fichier impossible', ['backup_id' => $id, 'file_path' => $real], $context);
                return ['ok' => false, 'message' => 'Suppression physique impossible (droits ou verrou).'];
            }

            $del = $this->pdo->prepare('DELETE FROM ' . $this->backupsTable . ' WHERE id = :id');
            $del->execute(['id' => $id]);
            $this->pdo->commit();

            $this->logAudit('backup.delete', 'success', 'Sauvegarde supprimée', ['backup_id' => $id, 'file_path' => $real], $context);
            return ['ok' => true, 'message' => 'Sauvegarde supprimée: ' . basename($real)];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logAudit('backup.delete', 'error', 'Echec suppression backup', ['error' => substr($e->getMessage(), 0, 240), 'name' => $name], $context);
            return ['ok' => false, 'message' => 'Échec suppression: ' . substr($e->getMessage(), 0, 180)];
        } finally {
            $this->releaseLock($lock);
        }
    }

    /** @return array<string, mixed> */
    public function restoreBackup(string $name, string $mode, bool $dryRun, bool $createPreRestoreSnapshot, array $context = []): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, self::SUPPORTED_RESTORE_MODES, true)) {
            return ['ok' => false, 'message' => 'Mode restore invalide.'];
        }

        $read = $this->readBackup($name);
        if (!((bool) ($read['ok'] ?? false))) {
            return ['ok' => false, 'message' => (string) ($read['message'] ?? 'Sauvegarde introuvable.')];
        }

        $manifest = (array) ($read['manifest'] ?? []);
        $analysis = $this->restoreCompatibility($manifest, $mode);
        if ($dryRun) {
            $this->logAudit('backup.restore.dry_run', 'success', 'Dry-run restore execute', ['name' => $name, 'mode' => $mode, 'analysis' => $analysis], $context);
            return [
                'ok' => true,
                'dry_run' => true,
                'analysis' => $analysis,
                'message' => 'Dry-run termine. Aucun ecrasement applique.',
            ];
        }

        if (!empty($analysis['blocking'])) {
            $this->logAudit('backup.restore', 'error', 'Restore bloque par incompatibilite', ['name' => $name, 'mode' => $mode, 'blocking' => $analysis['blocking']], $context);
            return ['ok' => false, 'message' => 'Restore bloque: ' . implode(' | ', (array) $analysis['blocking'])];
        }

        $lock = $this->acquireLock();
        if ($lock === null) {
            return ['ok' => false, 'message' => 'Restore concurrent détecté.'];
        }

        try {
            if ($createPreRestoreSnapshot) {
                $snapshot = $this->createBackup('pre_restore_snapshot', array_merge($context, ['origin' => 'pre_restore_auto']));
                if (!((bool) ($snapshot['ok'] ?? false))) {
                    return ['ok' => false, 'message' => 'Snapshot pre-restore impossible: ' . (string) ($snapshot['message'] ?? 'Erreur')];
                }
            }

            $path = (string) ($read['path'] ?? '');
            $result = match ($mode) {
                'db_only' => $this->restoreDbOnly($path),
                'files_only' => $this->restoreFilesOnly($path),
                'full' => $this->restoreFull($path),
                default => ['ok' => false, 'message' => 'Mode restore non supporte.'],
            };

            $this->insertRestoreRow($path, $result, $mode, $analysis);
            $this->logAudit('backup.restore', (bool) ($result['ok'] ?? false) ? 'success' : 'error', 'Restore execute', [
                'name' => $name,
                'mode' => $mode,
                'result' => $result,
                'analysis' => $analysis,
            ], $context);

            return $result;
        } catch (Throwable $e) {
            $this->logAudit('backup.restore', 'error', 'Echec restore', ['name' => $name, 'mode' => $mode, 'error' => substr($e->getMessage(), 0, 240)], $context);
            return ['ok' => false, 'message' => 'Echec restore: ' . substr($e->getMessage(), 0, 180)];
        } finally {
            $this->releaseLock($lock);
        }
    }

    /** @return array<string, mixed> */
    public function diagnostics(): array
    {
        $backups = $this->listBackups(500);
        $total = 0;
        $lastBackup = '-';
        $orphans = 0;
        foreach ($backups as $backup) {
            $total += (int) ($backup['size'] ?? 0);
            if ($lastBackup === '-') {
                $lastBackup = (string) ($backup['date'] ?? '-');
            }
            if (!empty($backup['is_orphan'])) {
                $orphans++;
            }
        }

        $lastRestore = '-';
        $lastFailure = '-';
        try {
            $restoreStmt = $this->pdo->query("SELECT created_at, status, last_error FROM " . $this->backupsTable . " WHERE backup_type = 'restore' ORDER BY created_at DESC LIMIT 1");
            $restore = $restoreStmt !== false ? $restoreStmt->fetch(PDO::FETCH_ASSOC) : false;
            if (is_array($restore)) {
                $lastRestore = (string) ($restore['created_at'] ?? '-');
                if (strtolower((string) ($restore['status'] ?? '')) !== 'success') {
                    $lastFailure = (string) ($restore['last_error'] ?? 'restore.failed');
                }
            }

            $failureStmt = $this->pdo->query(
                "SELECT created_at, last_error, status, backup_type FROM " . $this->backupsTable
                . " WHERE (LOWER(COALESCE(status, '')) IN ('failed','error') OR TRIM(COALESCE(last_error, '')) != '')"
                . " ORDER BY created_at DESC LIMIT 1"
            );
            $failure = $failureStmt !== false ? $failureStmt->fetch(PDO::FETCH_ASSOC) : false;
            if (is_array($failure)) {
                $failureDate = (string) ($failure['created_at'] ?? '-');
                $failureMsg = trim((string) ($failure['last_error'] ?? ''));
                $failureType = trim((string) ($failure['backup_type'] ?? 'backup'));
                if ($failureMsg === '') {
                    $failureMsg = 'statut=' . (string) ($failure['status'] ?? 'failed');
                }
                $lastFailure = $failureDate . ' [' . $failureType . '] ' . $failureMsg;
            }
        } catch (Throwable) {
        }

        if ($lastFailure === '-') {
            $logFailure = $this->lastBackupRuntimeFailureFromLogs();
            if ($logFailure !== '') {
                $lastFailure = $logFailure;
            }
        }

        $storageOk = is_dir($this->backupRoot) && is_writable($this->backupRoot);

        return [
            'last_backup' => $lastBackup,
            'last_restore' => $lastRestore,
            'last_failure' => $lastFailure,
            'total_size_bytes' => $total,
            'backup_format_version' => $this->backupFormatVersion,
            'core_version' => Version::current(),
            'storage_ok' => $storageOk,
            'storage_path' => $this->backupRoot,
            'orphans' => $orphans,
            'count' => count($backups),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function auditLog(int $limit = 120): array
    {
        $limit = max(1, min(500, $limit));
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, action, result, message, actor_user_id, actor_username, ip_address, created_at, context FROM ' . $this->auditTable . ' ORDER BY created_at DESC LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(function (array $row): array {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'action' => (string) ($row['action'] ?? ''),
                    'result' => (string) ($row['result'] ?? ''),
                    'message' => (string) ($row['message'] ?? ''),
                    'actor_user_id' => (int) ($row['actor_user_id'] ?? 0),
                    'actor_username' => (string) ($row['actor_username'] ?? ''),
                    'ip_address' => (string) ($row['ip_address'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'context' => $this->decodeManifest((string) ($row['context'] ?? '')),
                ];
            }, $rows);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function maintenanceLevels(): array
    {
        return [
            [
                'level' => 0,
                'label' => 'Niveau 0 - Désactivé',
                'access' => 'Tous les flux normaux',
                'blocked' => 'Aucun blocage maintenance',
                'allowed' => 'Toutes opérations normales',
                'usage' => 'Exploitation standard',
            ],
            [
                'level' => 1,
                'label' => 'Niveau 1 - Maintenance légère',
                'access' => 'Front bloqué, admins autorisés selon règles',
                'blocked' => 'Front public',
                'allowed' => 'Opérations non destructives admin',
                'usage' => 'Intervention courte et preventive',
            ],
            [
                'level' => 2,
                'label' => 'Niveau 2 - Maintenance technique',
                'access' => 'Admins limités selon politique active',
                'blocked' => 'Front + opérations non conformes',
                'allowed' => 'Backup/restore/migrations contrôlés',
                'usage' => 'Interventions techniques encadrées',
            ],
            [
                'level' => 3,
                'label' => 'Niveau 3 - Maintenance lourde',
                'access' => 'Superadmin + whitelists',
                'blocked' => 'Accès admin général',
                'allowed' => 'Opérations critiques',
                'usage' => 'Réparations structurelles/DB',
            ],
            [
                'level' => 4,
                'label' => 'Niveau 4 - Verrouillage total',
                'access' => 'Superadmin whitelisté uniquement',
                'blocked' => 'Tout hors exception explicite',
                'allowed' => 'Intervention critique sécurisée',
                'usage' => 'Restore complet et incident majeur',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function registerBackupFailure(string $type, string $path, array $manifest, array $context, string $message): array
    {
        try {
            $this->insertBackupRow($type, $path, 'failed', '', 0, $context, $manifest, $message);
        } catch (Throwable) {
        }

        $this->logAudit('backup.create', 'error', 'Échec création backup', ['error' => substr($message, 0, 240), 'type' => $type, 'path' => $path], $context);
        return ['ok' => false, 'message' => $message];
    }

    private function prepareZipTemporaryDirectory(): void
    {
        $tmpDir = CATMIN_STORAGE . '/tmp/zip';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            @ini_set('sys_temp_dir', $tmpDir);
            @putenv('TMPDIR=' . $tmpDir);
            @putenv('TMP=' . $tmpDir);
            @putenv('TEMP=' . $tmpDir);
        }
    }

    /** @return array<string, mixed> */
    private function createSqlFallbackBackup(string $type, string $stamp, string $sqlDump, array $manifest, array $context): array
    {
        if ($sqlDump === '') {
            return ['ok' => false, 'message' => 'Aucun dump SQL disponible pour fallback.'];
        }

        $filename = 'catmin-' . $type . '-' . $stamp . '.sql';
        $path = $this->backupRoot . '/' . $filename;

        $header = '-- Backup fallback (SQL)\n'
            . '-- backup_format_version: ' . $this->backupFormatVersion . "\n"
            . '-- core_version: ' . Version::current() . "\n"
            . '-- generated_at: ' . gmdate('c') . "\n\n";

        $written = @file_put_contents($path, $header . $sqlDump);
        if ($written === false) {
            return ['ok' => false, 'message' => 'Fallback SQL impossible (droits écriture).'];
        }

        $size = (int) (@filesize($path) ?: 0);
        $checksum = (string) (@hash_file('sha256', $path) ?: '');
        $manifest['file'] = ['name' => $filename, 'size' => $size, 'checksum_sha256' => $checksum];
        $manifest['fallback'] = ['mode' => 'sql_file'];

        $rowId = $this->insertBackupRow($type, $path, 'success', $checksum, $size, $context, $manifest);
        $this->logAudit('backup.create', 'warning', 'Sauvegarde créée via fallback SQL', ['backup_id' => $rowId, 'file' => $filename, 'type' => $type], $context);

        return [
            'ok' => true,
            'id' => $rowId,
            'name' => $filename,
            'path' => $path,
            'size' => $size,
            'checksum' => $checksum,
            'manifest' => $manifest,
            'message' => 'Sauvegarde créée (fallback SQL): ' . $filename,
        ];
    }

    /** @return array<string, mixed> */
    private function buildManifest(string $type, array $context): array
    {
        return [
            'id' => bin2hex(random_bytes(8)),
            'backup_format_version' => $this->backupFormatVersion,
            'backup_type' => $type,
            'created_at' => gmdate('c'),
            'core_version' => Version::current(),
            'origin' => (string) ($context['origin'] ?? 'manual'),
            'created_by' => [
                'user_id' => (int) ($context['user_id'] ?? 0),
                'username' => (string) ($context['username'] ?? ''),
                'ip' => (string) ($context['ip'] ?? ''),
            ],
            'content' => [
                'sql' => ['included' => false, 'tables_count' => 0, 'bytes' => 0],
                'files' => ['included' => false, 'items' => 0],
                'uploads' => ['included' => false],
                'config' => ['included' => false],
                'assets' => ['included' => false],
                'modules' => ['included' => false],
                'logs' => ['included' => false],
            ],
        ];
    }

    private function addContentByType(\ZipArchive $zip, string $type, array &$manifest): void
    {
        $addDir = function (string $sourceDir, string $targetPrefix) use ($zip, &$manifest): int {
            if (!is_dir($sourceDir)) {
                return 0;
            }
            $count = 0;
            $root = rtrim($sourceDir, '/');
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $path = (string) $item->getPathname();
                $relative = ltrim(substr($path, strlen($root)), '/');
                if ($relative === '') {
                    continue;
                }
                $entry = trim($targetPrefix . '/' . $relative, '/');
                if ($item->isDir()) {
                    $zip->addEmptyDir($entry);
                    continue;
                }
                $zip->addFile($path, $entry);
                $count++;
            }
            return $count;
        };

        $includeFiles = in_array($type, ['files_only', 'db_files', 'full_instance', 'pre_update_snapshot', 'pre_restore_snapshot'], true);
        if (!$includeFiles) {
            return;
        }

        $total = 0;
        $total += $addDir(CATMIN_ROOT . '/config', 'files/config');
        $manifest['content']['config']['included'] = true;

        $uploadsPath = CATMIN_ROOT . '/public/uploads';
        if (is_dir($uploadsPath)) {
            $total += $addDir($uploadsPath, 'files/public/uploads');
            $manifest['content']['uploads']['included'] = true;
        }

        $assetsPath = CATMIN_ROOT . '/public/assets';
        if (is_dir($assetsPath)) {
            $total += $addDir($assetsPath, 'files/public/assets');
            $manifest['content']['assets']['included'] = true;
        }

        $modulesPath = CATMIN_ROOT . '/modules';
        if (is_dir($modulesPath) && in_array($type, ['full_instance', 'pre_update_snapshot', 'pre_restore_snapshot'], true)) {
            $total += $addDir($modulesPath, 'files/modules');
            $manifest['content']['modules']['included'] = true;
        }

        $manifest['content']['files']['included'] = true;
        $manifest['content']['files']['items'] = $total;
    }

    private function injectManifestIntoZip(string $path, array $manifest): void
    {
        if (!class_exists('ZipArchive') || !is_file($path)) {
            return;
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return;
        }
        $zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();
    }

    private function countSqlTables(string $sql): int
    {
        if ($sql === '') {
            return 0;
        }
        return preg_match_all('/CREATE\s+TABLE\s+/i', $sql) ?: 0;
    }

    private function buildSqlDump(): string
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lines = [
            '-- CATMIN SQL dump',
            '-- Generated: ' . gmdate('c'),
            '-- Driver: ' . $driver,
            '',
        ];

        $quoteIdent = static function (string $identifier) use ($driver): string {
            if ($driver === 'pgsql') {
                return '"' . str_replace('"', '""', $identifier) . '"';
            }
            return '`' . str_replace('`', '``', $identifier) . '`';
        };

        $exportRows = function (string $tableName) use (&$lines, $quoteIdent): void {
            $tableIdent = $quoteIdent($tableName);
            $rowsStmt = $this->pdo->query('SELECT * FROM ' . $tableIdent);
            $rows = $rowsStmt !== false ? ($rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            if ($rows === []) {
                return;
            }
            foreach ($rows as $row) {
                $columns = [];
                $values = [];
                foreach ($row as $col => $value) {
                    $columns[] = $quoteIdent((string) $col);
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $values[] = $value ? '1' : '0';
                    } elseif (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/', $value) === 1)) {
                        $values[] = (string) $value;
                    } else {
                        $values[] = $this->pdo->quote((string) $value);
                    }
                }
                $lines[] = 'INSERT INTO ' . $tableIdent . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
            }
            $lines[] = '';
        };

        if ($driver === 'sqlite') {
            $tablesStmt = $this->pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name ASC");
            $tables = $tablesStmt !== false ? ($tablesStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($tables as $table) {
                $name = (string) ($table['name'] ?? '');
                $sql = trim((string) ($table['sql'] ?? ''));
                if ($name === '' || $sql === '') {
                    continue;
                }
                $lines[] = $sql . ';';
                $lines[] = '';
                $exportRows($name);
            }
        } else {
            $tablesStmt = $this->pdo->query('SHOW TABLES');
            $tables = $tablesStmt !== false ? ($tablesStmt->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];
            foreach ($tables as $tableNameRaw) {
                $tableName = (string) $tableNameRaw;
                if ($tableName === '') {
                    continue;
                }
                $createStmt = $this->pdo->query('SHOW CREATE TABLE ' . $quoteIdent($tableName));
                $createRow = $createStmt !== false ? $createStmt->fetch(PDO::FETCH_ASSOC) : false;
                if (is_array($createRow)) {
                    foreach ($createRow as $k => $v) {
                        if (is_string($k) && stripos($k, 'create table') !== false && is_string($v) && $v !== '') {
                            $lines[] = $v . ';';
                            $lines[] = '';
                            break;
                        }
                    }
                }
                $exportRows($tableName);
            }
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function findByName(string $name): ?array
    {
        $name = basename(trim($name));
        if ($name === '') {
            return null;
        }

        try {
            $stmt = $this->pdo->query('SELECT * FROM ' . $this->backupsTable . ' ORDER BY created_at DESC LIMIT 500');
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($rows as $row) {
                $path = (string) ($row['file_path'] ?? '');
                if ($path !== '' && basename($path) === $name) {
                    return $row;
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function insertBackupRow(string $type, string $path, string $status, string $checksum, int $size, array $context, array $manifest, ?string $lastError = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . $this->backupsTable . ' (backup_type, status, file_path, checksum, size_bytes, created_at, backup_format_version, core_version, origin, manifest, integrity_status, is_orphan, created_by_user_id, created_by_username, last_error) '
            . 'VALUES (:backup_type, :status, :file_path, :checksum, :size_bytes, :created_at, :backup_format_version, :core_version, :origin, :manifest, :integrity_status, :is_orphan, :created_by_user_id, :created_by_username, :last_error)'
        );
        $stmt->execute([
            'backup_type' => $type,
            'status' => $status,
            'file_path' => $path,
            'checksum' => $checksum,
            'size_bytes' => $size,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'backup_format_version' => $this->backupFormatVersion,
            'core_version' => Version::current(),
            'origin' => (string) ($context['origin'] ?? 'manual'),
            'manifest' => (string) json_encode($manifest, JSON_UNESCAPED_SLASHES),
            'integrity_status' => 'ok',
            'is_orphan' => 0,
            'created_by_user_id' => (int) ($context['user_id'] ?? 0),
            'created_by_username' => (string) ($context['username'] ?? ''),
            'last_error' => $lastError,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertRestoreRow(string $sourcePath, array $result, string $mode, array $analysis): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . $this->backupsTable . ' (backup_type, status, file_path, checksum, size_bytes, created_at, backup_format_version, core_version, origin, manifest, integrity_status, is_orphan, created_by_user_id, created_by_username, last_error) '
            . 'VALUES (:backup_type, :status, :file_path, :checksum, :size_bytes, :created_at, :backup_format_version, :core_version, :origin, :manifest, :integrity_status, :is_orphan, :created_by_user_id, :created_by_username, :last_error)'
        );
        $stmt->execute([
            'backup_type' => 'restore',
            'status' => (bool) ($result['ok'] ?? false) ? 'success' : 'failed',
            'file_path' => $sourcePath,
            'checksum' => null,
            'size_bytes' => 0,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'backup_format_version' => $this->backupFormatVersion,
            'core_version' => Version::current(),
            'origin' => 'manual_restore_' . $mode,
            'manifest' => (string) json_encode(['mode' => $mode, 'analysis' => $analysis], JSON_UNESCAPED_SLASHES),
            'integrity_status' => (bool) ($result['ok'] ?? false) ? 'ok' : 'failed',
            'is_orphan' => 0,
            'created_by_user_id' => 0,
            'created_by_username' => '',
            'last_error' => (string) (($result['ok'] ?? false) ? '' : ($result['message'] ?? 'restore.failed')),
        ]);
    }

    private function restoreDbOnly(string $path): array
    {
        if (!class_exists('ZipArchive') || !is_file($path)) {
            return ['ok' => false, 'message' => 'Archive restore illisible.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'message' => 'Ouverture backup impossible.'];
        }

        $sqliteEntryIndex = $zip->locateName('database.sqlite', \ZipArchive::FL_NOCASE);
        if ($sqliteEntryIndex === false) {
            $sqliteEntryIndex = $zip->locateName('db/database.sqlite', \ZipArchive::FL_NOCASE);
        }
        $sqlEntryIndex = $zip->locateName('db/dump.sql', \ZipArchive::FL_NOCASE);

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'sqlite') {
            $zip->close();
            return ['ok' => false, 'message' => 'Restore DB automatise supporte uniquement en SQLite pour le moment.'];
        }

        $targetDb = (string) config('database.connections.sqlite.database', CATMIN_ROOT . '/db/database.sqlite');
        if ($targetDb === '') {
            $zip->close();
            return ['ok' => false, 'message' => 'Chemin SQLite invalide.'];
        }

        $tmpDir = CATMIN_STORAGE . '/tmp/restore';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        if ($sqliteEntryIndex !== false) {
            $tmpDb = $tmpDir . '/restore.sqlite';
            if (!$zip->extractTo($tmpDir, [$zip->getNameIndex((int) $sqliteEntryIndex)])) {
                $zip->close();
                return ['ok' => false, 'message' => 'Extraction SQLite backup impossible.'];
            }

            $entryName = (string) $zip->getNameIndex((int) $sqliteEntryIndex);
            $sourceDb = $tmpDir . '/' . $entryName;
            if (!is_file($sourceDb)) {
                $zip->close();
                return ['ok' => false, 'message' => 'SQLite source manquant.'];
            }

            if (!@copy($sourceDb, $targetDb)) {
                $zip->close();
                return ['ok' => false, 'message' => 'Ecriture SQLite cible impossible.'];
            }
            $zip->close();
            return ['ok' => true, 'message' => 'Restore DB SQLite termine.'];
        }

        if ($sqlEntryIndex !== false) {
            $sql = (string) $zip->getFromIndex((int) $sqlEntryIndex);
            $zip->close();
            if ($sql === '') {
                return ['ok' => false, 'message' => 'Dump SQL vide.'];
            }
            try {
                $this->pdo->exec('PRAGMA foreign_keys = OFF');
                $this->pdo->exec($sql);
                $this->pdo->exec('PRAGMA foreign_keys = ON');
                return ['ok' => true, 'message' => 'Restore SQL applique.'];
            } catch (Throwable $e) {
                return ['ok' => false, 'message' => 'Echec execution SQL: ' . substr($e->getMessage(), 0, 180)];
            }
        }

        $zip->close();
        return ['ok' => false, 'message' => 'Aucun contenu DB restaurable detecte.'];
    }

    private function restoreFilesOnly(string $path): array
    {
        if (!class_exists('ZipArchive') || !is_file($path)) {
            return ['ok' => false, 'message' => 'Archive restore illisible.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'message' => 'Ouverture backup impossible.'];
        }

        $extracted = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            if (!str_starts_with($entry, 'files/')) {
                continue;
            }

            $relative = ltrim(substr($entry, 6), '/');
            if ($relative === '') {
                continue;
            }

            $target = CATMIN_ROOT . '/' . $relative;
            $targetDir = dirname($target);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }

            if (str_ends_with($entry, '/')) {
                if (!is_dir($target)) {
                    @mkdir($target, 0775, true);
                }
                continue;
            }

            $content = $zip->getFromIndex($i);
            if ($content === false) {
                continue;
            }

            if (@file_put_contents($target, $content) === false) {
                $zip->close();
                return ['ok' => false, 'message' => 'Ecriture fichier restore impossible: ' . $relative];
            }
            $extracted++;
        }

        $zip->close();
        if ($extracted === 0) {
            return ['ok' => false, 'message' => 'Aucun fichier restaurable detecte dans le backup.'];
        }

        return ['ok' => true, 'message' => 'Restore fichiers termine (' . $extracted . ' elements).'];
    }

    private function restoreFull(string $path): array
    {
        $db = $this->restoreDbOnly($path);
        if (!((bool) ($db['ok'] ?? false))) {
            return $db;
        }

        $files = $this->restoreFilesOnly($path);
        if (!((bool) ($files['ok'] ?? false))) {
            return ['ok' => false, 'message' => 'DB restauree mais fichiers en echec: ' . (string) ($files['message'] ?? '')];
        }

        return ['ok' => true, 'message' => 'Restore complet termine (DB + fichiers).'];
    }

    /** @return array<string, mixed> */
    private function restoreCompatibility(array $manifest, string $mode): array
    {
        $warnings = [];
        $blocking = [];

        $backupCoreVersion = (string) ($manifest['core_version'] ?? '');
        $currentCore = Version::current();
        if ($backupCoreVersion !== '' && $backupCoreVersion !== $currentCore) {
            $warnings[] = 'Core different: backup=' . $backupCoreVersion . ' current=' . $currentCore;
        }

        $format = (string) ($manifest['backup_format_version'] ?? '');
        if ($format !== '' && version_compare($format, $this->backupFormatVersion, '<')) {
            $warnings[] = 'Format backup ancien: ' . $format;
        }

        $content = (array) ($manifest['content'] ?? []);
        $hasSql = (bool) (($content['sql']['included'] ?? false));
        $hasFiles = (bool) (($content['files']['included'] ?? false));

        if ($mode === 'db_only' && !$hasSql) {
            $blocking[] = 'Ce backup ne contient pas de contenu DB.';
        }
        if ($mode === 'files_only' && !$hasFiles) {
            $blocking[] = 'Ce backup ne contient pas de contenu fichiers.';
        }
        if ($mode === 'full' && (!$hasSql || !$hasFiles)) {
            $blocking[] = 'Restore complet impossible: DB ou fichiers absents.';
        }

        return ['warnings' => $warnings, 'blocking' => $blocking];
    }

    /** @return array<string, mixed> */
    private function analyzeBackupContents(string $path, array $manifest): array
    {
        $isTextPreview = false;
        $previewText = '';

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['sql', 'json', 'txt', 'md', 'log'], true)) {
            $isTextPreview = true;
            $previewText = mb_substr((string) @file_get_contents($path), 0, 250000);
        } elseif ($ext === 'zip' && class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $manifestRaw = (string) $zip->getFromName('manifest.json');
                if ($manifestRaw !== '') {
                    $isTextPreview = true;
                    $previewText = mb_substr($manifestRaw, 0, 250000);
                }
                $zip->close();
            }
        }

        $content = (array) ($manifest['content'] ?? []);

        return [
            'is_text_preview' => $isTextPreview,
            'preview_text' => $previewText,
            'backup_type' => (string) ($manifest['backup_type'] ?? ''),
            'backup_format_version' => (string) ($manifest['backup_format_version'] ?? ''),
            'core_version' => (string) ($manifest['core_version'] ?? ''),
            'content' => [
                'sql_full' => (bool) ($content['sql']['included'] ?? false),
                'sql_tables_count' => (int) ($content['sql']['tables_count'] ?? 0),
                'files' => (bool) ($content['files']['included'] ?? false),
                'uploads' => (bool) ($content['uploads']['included'] ?? false),
                'config' => (bool) ($content['config']['included'] ?? false),
                'assets' => (bool) ($content['assets']['included'] ?? false),
                'modules' => (bool) ($content['modules']['included'] ?? false),
                'logs' => (bool) ($content['logs']['included'] ?? false),
            ],
            'warnings' => $this->restoreCompatibility($manifest, 'full')['warnings'] ?? [],
        ];
    }

    private function loadManifestFromBackup(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'zip' && class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $raw = (string) $zip->getFromName('manifest.json');
                $zip->close();
                return $this->decodeManifest($raw);
            }
        }

        return [];
    }

    private function checkIntegrity(string $path, string $expectedChecksum): array
    {
        if (!is_file($path)) {
            return ['ok' => false, 'message' => 'fichier.absent'];
        }

        if ($expectedChecksum === '') {
            return ['ok' => true, 'message' => 'checksum.absent'];
        }

        $actual = (string) (@hash_file('sha256', $path) ?: '');
        if ($actual === '' || !hash_equals($expectedChecksum, $actual)) {
            return ['ok' => false, 'message' => 'checksum.invalide'];
        }

        return ['ok' => true, 'message' => 'checksum.ok'];
    }

    private function decodeManifest(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function logAudit(string $action, string $result, string $message, array $context = [], array $actor = []): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ' . $this->auditTable . ' (action, result, message, actor_user_id, actor_username, ip_address, context, created_at) '
                . 'VALUES (:action, :result, :message, :actor_user_id, :actor_username, :ip_address, :context, :created_at)'
            );
            $stmt->execute([
                'action' => substr($action, 0, 120),
                'result' => substr($result, 0, 40),
                'message' => substr($message, 0, 255),
                'actor_user_id' => (int) ($actor['user_id'] ?? 0),
                'actor_username' => substr((string) ($actor['username'] ?? ''), 0, 120),
                'ip_address' => substr((string) ($actor['ip'] ?? ''), 0, 64),
                'context' => (string) json_encode($context, JSON_UNESCAPED_SLASHES),
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }
    }

    private function acquireLock()
    {
        $dir = dirname($this->lockPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        $fp = @fopen($this->lockPath, 'c+');
        if ($fp === false) {
            return null;
        }
        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            @fclose($fp);
            return null;
        }
        return $fp;
    }

    private function releaseLock($fp): void
    {
        if (is_resource($fp)) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    private function lastBackupRuntimeFailureFromLogs(): string
    {
        $logFiles = [
            CATMIN_STORAGE . '/logs/catmin.log',
            CATMIN_ROOT . '/logs/catmin.log',
        ];

        foreach ($logFiles as $logFile) {
            if (!is_file($logFile)) {
                continue;
            }

            $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!is_array($lines) || $lines === []) {
                continue;
            }

            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = (string) $lines[$i];
                if (stripos($line, '/maintenance/backup/create') === false) {
                    continue;
                }
                if (stripos($line, 'ERROR') === false) {
                    continue;
                }
                $timestamp = '';
                if (preg_match('/^\[([^\]]+)\]/', $line, $m) === 1) {
                    $timestamp = trim((string) ($m[1] ?? ''));
                }

                $runtimeMessage = '';
                if (preg_match('/"message":"([^"]+)"/', $line, $m) === 1) {
                    $runtimeMessage = stripcslashes((string) ($m[1] ?? ''));
                }

                if ($runtimeMessage !== '') {
                    return ($timestamp !== '' ? ('[' . $timestamp . '] ') : '') . $runtimeMessage;
                }

                return mb_substr($line, 0, 220);
            }
        }

        return '';
    }
}
