<?php

declare(strict_types=1);

namespace Install;

final class InstallerPrecheck
{
    /**
     * @return array{
     *     checks: array<int, array{key:string,label:string,required:bool,ok:bool,details:string,status_text:string,status_class:string,category:string}>,
     *     categories: array<int, array{key:string,title:string,requisites:string,prerequisites:string,checks:array<int, array{key:string,label:string,required:bool,ok:bool,details:string,status_text:string,status_class:string,category:string}>}>,
     *     summary: array{total:int,passed:int,failed:int,required_failed:int}
     * }
     */
    public function run(): array
    {
        $checks = [];

        // Categorie principale demandee: PHP 8.3.0
        $this->add($checks, 'runtime', 'php_8300', 'Version PHP 8.3.0', PHP_VERSION_ID >= 80300, true, 'Version detectee: ' . PHP_VERSION . '. Minimum attendu: 8.3.0.');
        $this->add($checks, 'runtime', 'session', 'Session active', extension_loaded('session') || function_exists('session_start'), true, 'Session requise pour auth et installation.');
        $this->add($checks, 'runtime', 'ctype', 'Extension ctype', extension_loaded('ctype') || function_exists('ctype_alnum'), true, 'Validation de chaines.');
        $this->add($checks, 'runtime', 'filter', 'Extension filter', extension_loaded('filter') || function_exists('filter_var'), true, 'Sanitization/validation.');
        $this->add($checks, 'runtime', 'hash', 'Extension hash', extension_loaded('hash') || function_exists('hash'), true, 'Hash/signatures.');

        // Extensions demandees (sans optionnel)
        $this->add($checks, 'extensions', 'pdo', 'Extension pdo', extension_loaded('pdo'), true, 'Requise pour toutes les bases de donnees.');
        $this->add($checks, 'extensions', 'pdo_mysql', 'Extension pdo_mysql', extension_loaded('pdo_mysql'), true, 'Requise pour MySQL/MariaDB.');
        $this->add($checks, 'extensions', 'mbstring', 'Extension mbstring', extension_loaded('mbstring'), true, 'UTF-8 et multioctets.');
        $this->add($checks, 'extensions', 'json', 'Extension json', extension_loaded('json'), true, 'JSON runtime.');
        $this->add($checks, 'extensions', 'fileinfo', 'Extension fileinfo', extension_loaded('fileinfo'), true, 'Detection MIME.');
        $this->add($checks, 'extensions', 'openssl', 'Extension openssl', extension_loaded('openssl'), true, 'Crypto et securite.');
        $this->add($checks, 'extensions', 'curl', 'Extension curl', extension_loaded('curl'), true, 'HTTP sortant.');
        $this->add($checks, 'extensions', 'gd', 'Extension gd', extension_loaded('gd'), true, 'Traitement image de base.');
        $this->add($checks, 'extensions', 'intl', 'Extension intl', extension_loaded('intl'), true, 'Locales et formats.');
        $this->add($checks, 'extensions', 'tokenizer', 'Extension tokenizer', extension_loaded('tokenizer') || function_exists('token_get_all'), true, 'Parsing.');
        $this->add($checks, 'extensions', 'sodium', 'Extension sodium', extension_loaded('sodium'), true, 'Crypto moderne.');
        $this->add($checks, 'extensions', 'password_hash', 'Fonction password_hash()', function_exists('password_hash'), true, 'Hash mot de passe moderne.');
        $this->add($checks, 'extensions', 'zip', 'Extension zip', extension_loaded('zip'), true, 'Archives ZIP.');
        $this->add($checks, 'extensions', 'phar', 'Extension phar', extension_loaded('phar'), true, 'Archives PHAR.');
        $this->add($checks, 'extensions', 'spl', 'Extension SPL', extension_loaded('spl') || function_exists('spl_autoload_register'), true, 'Autoload/iterateurs.');
        $this->add($checks, 'extensions', 'ftp', 'Extension ftp', extension_loaded('ftp'), true, 'Transfert FTP natif.');
        $this->add($checks, 'extensions', 'opcache', 'Extension opcache', extension_loaded('Zend OPcache') || extension_loaded('opcache') || (bool) ini_get('opcache.enable'), true, 'Performance PHP.');
        $this->add($checks, 'extensions', 'apcu', 'Extension apcu', extension_loaded('apcu'), true, 'Cache applicatif local.');
        $this->add($checks, 'extensions', 'exif', 'Extension exif', extension_loaded('exif'), true, 'Metadonnees image.');
        $this->add($checks, 'extensions', 'imagick', 'Extension imagick', extension_loaded('imagick'), true, 'Pipeline image avance.');
        $this->add($checks, 'extensions', 'redis', 'Extension redis', extension_loaded('redis'), true, 'Cache/queue redis.');
        $this->add($checks, 'extensions', 'memcached', 'Extension memcached', extension_loaded('memcached'), true, 'Cache distribue memcached.');
        $this->add($checks, 'extensions', 'xdebug', 'Extension xdebug', extension_loaded('xdebug'), true, 'Diagnostic et debug.');
        $this->add($checks, 'extensions', 'bcmath', 'Extension bcmath', extension_loaded('bcmath'), true, 'Calcul decimal precis.');
        $this->add($checks, 'extensions', 'gmp', 'Extension gmp', extension_loaded('gmp'), true, 'Grands entiers.');

        // Infrastructure/systeme (architecture racine: cache/logs/sessions/tmp/db au niveau de storage)
        $this->add($checks, 'system', 'pdo_driver_supported', 'Driver PDO supporte (sqlite/mysql/pgsql/sqlsrv)', $this->hasSupportedPdoDriver(), true, 'Drivers disponibles: ' . $this->availableDriversList());
        $this->add($checks, 'system', 'storage_present', 'Dossier storage présent', is_dir(CATMIN_STORAGE), true, 'Dossier technique présent (CHMOD usuel: 755).');
        $this->add($checks, 'system', 'env_creatable', 'Fichier .env créable/inscriptible', $this->isEnvCreatableOrWritable(), true, 'Le fichier peut ne pas exister avant install; le dossier parent doit permettre sa création.');
        $this->add($checks, 'system', 'cache_dir', 'Dossier cache writable', $this->isWritableOrCreatable(CATMIN_ROOT . '/cache'), true, 'Chemin attendu: /cache (racine projet).');
        $this->add($checks, 'system', 'logs_dir', 'Dossier logs writable', $this->isWritableOrCreatable(CATMIN_ROOT . '/logs'), true, 'Chemin attendu: /logs (racine projet).');
        $this->add($checks, 'system', 'sessions_dir', 'Dossier sessions writable', $this->isWritableOrCreatable(CATMIN_ROOT . '/sessions'), true, 'Chemin attendu: /sessions (racine projet).');
        $this->add($checks, 'system', 'tmp_dir', 'Dossier tmp writable', $this->isWritableOrCreatable(CATMIN_ROOT . '/tmp'), true, 'Chemin attendu: /tmp (racine projet).');
        $this->add($checks, 'system', 'db_dir', 'Dossier db writable', $this->isWritableOrCreatable(CATMIN_ROOT . '/db'), true, 'Chemin attendu: /db (racine projet, sqlite incluse).');
        $this->add($checks, 'system', 'disk_space', 'Espace disque >= 256MB', $this->hasEnoughDiskSpace(), true, 'Espace libre minimal conseille: 256MB.');

        $summary = $this->summarize($checks);

        return [
            'checks' => $checks,
            'categories' => $this->buildCategories($checks),
            'summary' => $summary,
        ];
    }

    private function add(array &$checks, string $category, string $key, string $label, bool $ok, bool $required, string $details): void
    {
        $status = $this->status($required, $ok);

        $checks[] = [
            'category' => $category,
            'key' => $key,
            'label' => $label,
            'required' => $required,
            'ok' => $ok,
            'details' => $details,
            'status_text' => $status['text'],
            'status_class' => $status['class'],
        ];
    }

    private function summarize(array $checks): array
    {
        $summary = [
            'total' => count($checks),
            'passed' => 0,
            'failed' => 0,
            'required_failed' => 0,
        ];

        foreach ($checks as $check) {
            $ok = (bool) ($check['ok'] ?? false);
            $required = (bool) ($check['required'] ?? false);

            if ($ok) {
                $summary['passed']++;
                continue;
            }

            $summary['failed']++;
            if ($required) {
                $summary['required_failed']++;
            }
        }

        return $summary;
    }

    private function buildCategories(array $checks): array
    {
        $definitions = [
            'runtime' => [
                'title' => 'PHP 8.3.0',
                'requisites' => 'Runtime PHP 8.3.0, fonctions coeur actives.',
                'prerequisites' => 'Session et extensions standard chargees.',
            ],
            'extensions' => [
                'title' => 'Extensions PHP',
                'requisites' => 'Toutes les extensions listees doivent etre presentes.',
                'prerequisites' => 'Activer les modules via php.ini et redemarrer PHP-FPM/Apache.',
            ],
            'system' => [
                'title' => 'Systeme & Stockage',
                'requisites' => 'Drivers DB + structure racine (cache/logs/sessions/tmp/db) opérationnelle.',
                'prerequisites' => 'Permissions minimales sûres (755/775) sans 777, et quota disque valide.',
            ],
        ];

        $grouped = [];
        foreach ($checks as $check) {
            $key = (string) ($check['category'] ?? 'system');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $check;
        }

        $categories = [];
        foreach (['runtime', 'extensions', 'system'] as $categoryKey) {
            $meta = $definitions[$categoryKey];
            $categories[] = [
                'key' => $categoryKey,
                'title' => $meta['title'],
                'requisites' => $meta['requisites'],
                'prerequisites' => $meta['prerequisites'],
                'checks' => $grouped[$categoryKey] ?? [],
            ];
        }

        return $categories;
    }

    private function hasSupportedPdoDriver(): bool
    {
        foreach (['sqlite', 'mysql', 'pgsql', 'sqlsrv'] as $driver) {
            if (in_array($driver, \PDO::getAvailableDrivers(), true)) {
                return true;
            }
        }

        return false;
    }

    private function availableDriversList(): string
    {
        $drivers = \PDO::getAvailableDrivers();

        if ($drivers === []) {
            return 'aucun';
        }

        return implode(', ', $drivers);
    }

    private function isWritableOrCreatable(string $path): bool
    {
        if (is_dir($path) || is_file($path)) {
            return is_writable($path);
        }

        return is_writable(dirname($path));
    }

    private function isEnvCreatableOrWritable(): bool
    {
        $envPath = CATMIN_ROOT . '/.env';

        if (is_file($envPath)) {
            return is_writable($envPath);
        }

        return is_writable(dirname($envPath));
    }

    private function hasEnoughDiskSpace(): bool
    {
        $bytes = @disk_free_space(CATMIN_ROOT);
        if (!is_int($bytes) && !is_float($bytes)) {
            return false;
        }

        return $bytes >= 268435456; // 256MB
    }

    /**
     * @return array{text:string,class:string}
     */
    private function status(bool $required, bool $ok): array
    {
        if ($required && !$ok) {
            return ['text' => 'X', 'class' => 'text-bg-danger'];
        }

        if ($required && $ok) {
            return ['text' => 'V', 'class' => 'text-bg-success'];
        }

        return ['text' => 'O', 'class' => 'text-bg-warning'];
    }
}
