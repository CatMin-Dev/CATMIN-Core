<?php

declare(strict_types=1);

final class CoreModuleCompatibilityChecker
{
    public function check(array $manifest): array
    {
        $errors = [];
        $warnings = [];
        $state = 'compatible';

        $phpMin = trim((string) ($manifest['php_min'] ?? ''));
        if ($phpMin !== '' && version_compare(PHP_VERSION, $phpMin, '<')) {
            $errors[] = 'PHP incompatible: requis ' . $phpMin . ', courant ' . PHP_VERSION;
        }
        $phpMax = trim((string) ($manifest['php_max'] ?? ''));
        if ($phpMax !== '' && version_compare(PHP_VERSION, $phpMax, '>')) {
            $errors[] = 'PHP incompatible: max ' . $phpMax . ', courant ' . PHP_VERSION;
        }

        $catminMin = trim((string) ($manifest['catmin_min'] ?? ''));
        if ($catminMin !== '') {
            require_once CATMIN_CORE . '/versioning/Version.php';
            $current = \Core\versioning\Version::current();
            if (@version_compare($current, $catminMin, '<')) {
                $errors[] = 'CATMIN incompatible: requis ' . $catminMin . ', courant ' . $current;
            }
        }
        $catminMax = trim((string) ($manifest['catmin_max'] ?? ''));
        if ($catminMax !== '') {
            require_once CATMIN_CORE . '/versioning/Version.php';
            $current = \Core\versioning\Version::current();
            if ($this->isHigherThanMax($current, $catminMax)) {
                $errors[] = 'CATMIN incompatible: max ' . $catminMax . ', courant ' . $current;
            }
        }

        $dbState = $this->checkDatabaseCompatibility($manifest);
        $errors = array_merge($errors, (array) ($dbState['errors'] ?? []));
        $warnings = array_merge($warnings, (array) ($dbState['warnings'] ?? []));

        if (array_key_exists('core_compatible', $manifest) && (bool) $manifest['core_compatible'] !== true) {
            $errors[] = 'Module non marqué core_compatible';
        }

        if ($errors !== []) {
            $state = 'incompatible';
        } elseif ($warnings !== []) {
            $state = 'compatible_with_warning';
        } elseif ((bool) ($dbState['unknown'] ?? false)) {
            $state = 'unknown';
        }

        return [
            'compatible' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'state' => $state,
        ];
    }

    private function isHigherThanMax(string $current, string $max): bool
    {
        $max = trim($max);
        if (preg_match('/^([0-9]+)\.x$/', $max, $m) === 1) {
            $major = (int) $m[1];
            $currentMajor = (int) explode('.', $current)[0];
            return $currentMajor > $major;
        }
        return @version_compare($current, $max, '>');
    }

    /** @return array{errors:array<int,string>,warnings:array<int,string>,unknown:bool} */
    private function checkDatabaseCompatibility(array $manifest): array
    {
        $errors = [];
        $warnings = [];
        $unknown = false;

        $currentDriver = strtolower(trim((string) config('database.default', '')));
        if ($currentDriver === '') {
            $warnings[] = 'Driver DB courant indéterminé';
            return ['errors' => $errors, 'warnings' => $warnings, 'unknown' => true];
        }

        $supported = is_array($manifest['db_supported'] ?? null) ? $manifest['db_supported'] : [];
        $supported = array_values(array_filter(array_map(
            static fn ($v): string => strtolower(trim((string) $v)),
            $supported
        ), static fn (string $v): bool => $v !== ''));
        if ($supported !== [] && !in_array($currentDriver, $supported, true)) {
            $errors[] = 'DB incompatible: driver ' . $currentDriver . ' non supporté';
        }

        $constraints = is_array($manifest['db_constraints'] ?? null) ? $manifest['db_constraints'] : [];
        $constraint = trim((string) ($constraints[$currentDriver] ?? ''));
        if ($constraint !== '') {
            $serverVersion = $this->resolveServerVersion($currentDriver);
            if ($serverVersion === null) {
                $warnings[] = 'Version DB non détectée pour contrainte ' . $currentDriver . ' ' . $constraint;
                $unknown = true;
            } elseif (!$this->matchVersionConstraint($serverVersion, $constraint)) {
                $errors[] = 'DB incompatible: contrainte ' . $currentDriver . ' ' . $constraint . ', serveur ' . $serverVersion;
            }
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'unknown' => $unknown,
        ];
    }

    private function resolveServerVersion(string $driver): ?string
    {
        try {
            require_once CATMIN_CORE . '/database/ConnectionManager.php';
            $manager = new \Core\database\ConnectionManager();
            $pdo = $manager->connection($driver);
            $raw = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            if ($raw === '') {
                return null;
            }
            if (preg_match('/([0-9]+(?:\.[0-9]+){1,2})/', $raw, $m) === 1) {
                return $m[1];
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchVersionConstraint(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);
        if ($constraint === '') {
            return true;
        }
        if (preg_match('/^(>=|<=|>|<|=)?\s*([0-9]+(?:\.[0-9]+){1,2})$/', $constraint, $m) !== 1) {
            return false;
        }
        $operator = $m[1] !== '' ? $m[1] : '=';
        $expected = $m[2];
        return version_compare($version, $expected, $operator);
    }
}
