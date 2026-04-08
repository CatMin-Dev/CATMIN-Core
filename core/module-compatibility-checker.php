<?php

declare(strict_types=1);

final class CoreModuleCompatibilityChecker
{
    public function check(array $manifest): array
    {
        $errors = [];

        $phpMin = trim((string) ($manifest['php_min'] ?? ''));
        if ($phpMin !== '' && version_compare(PHP_VERSION, $phpMin, '<')) {
            $errors[] = 'PHP incompatible: requis ' . $phpMin . ', courant ' . PHP_VERSION;
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

        if (array_key_exists('core_compatible', $manifest) && (bool) $manifest['core_compatible'] !== true) {
            $errors[] = 'Module non marqué core_compatible';
        }

        return [
            'compatible' => $errors === [],
            'errors' => $errors,
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
}
