<?php

declare(strict_types=1);

namespace Core\update;

use Core\database\ConnectionManager;
use Core\versioning\VersionManager;

require_once CATMIN_CORE . '/versioning/VersionManager.php';
require_once CATMIN_CORE . '/database/ConnectionManager.php';

final class UpdatePreflight
{
    public function run(?string $targetCoreVersion = null): array
    {
        $vm = new VersionManager();
        $currentCore = $vm->core();
        $expectedDb = $vm->dbExpected();
        $currentDb = $vm->db();

        $targetCoreVersion = $targetCoreVersion !== null && trim($targetCoreVersion) !== ''
            ? trim($targetCoreVersion)
            : $currentCore;

        $errors = [];
        $warnings = [];

        if (!$vm->formatValid($currentCore)) {
            $errors[] = 'Version core courante invalide.';
        }
        if (!$vm->formatValid($targetCoreVersion)) {
            $errors[] = 'Version core cible invalide.';
        }

        $moduleCompatibility = $vm->moduleCompatibility();
        if (!empty($moduleCompatibility['has_blocking'])) {
            $errors[] = 'Modules actifs incompatibles détectés.';
        }

        if ($currentDb !== $expectedDb) {
            $warnings[] = 'Version DB differente de la version attendue.';
        }

        if (!$this->hasRecentBackup()) {
            $warnings[] = 'Aucune sauvegarde recente detectee avant update.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'current_core' => $currentCore,
            'target_core' => $targetCoreVersion,
            'current_db' => $currentDb,
            'expected_db' => $expectedDb,
            'modules' => $moduleCompatibility,
            'backup_recommended' => true,
        ];
    }

    private function hasRecentBackup(): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'backups';
            $stmt = $pdo->query('SELECT created_at FROM ' . $table . ' ORDER BY created_at DESC LIMIT 1');
            $value = $stmt !== false ? $stmt->fetchColumn() : false;
            if (!is_string($value) || trim($value) === '') {
                return false;
            }
            $ts = strtotime($value);
            if ($ts === false) {
                return false;
            }
            return (time() - $ts) <= (7 * 24 * 3600);
        } catch (\Throwable) {
            return false;
        }
    }
}

