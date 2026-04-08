<?php

declare(strict_types=1);

final class CoreModuleValidator
{
    private const REQUIRED_FIELDS = [
        'name',
        'slug',
        'type',
        'version',
        'description',
        'author',
        'enabled',
        'core_compatible',
        'php_min',
        'catmin_min',
        'dependencies',
        'load',
        'routes',
        'migrations',
        'settings',
        'permissions',
    ];

    private const ALLOWED_TYPES = ['core', 'admin', 'front', 'integrations', 'drivers'];

    public function validate(array $manifest, string $modulePath): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $manifest)) {
                $errors[] = 'Champ manifest manquant: ' . $field;
            }
        }

        $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
        if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9\-]*$/', $slug) !== 1) {
            $errors[] = 'Slug invalide';
        }

        $type = strtolower(trim((string) ($manifest['type'] ?? '')));
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = 'Type invalide: ' . $type;
        }

        $version = trim((string) ($manifest['version'] ?? ''));
        if ($version === '' || preg_match('/^[0-9]+\.[0-9]+\.[0-9]+([\-+].*)?$/', $version) !== 1) {
            $errors[] = 'Version invalide';
        }

        $dependencies = $manifest['dependencies'] ?? [];
        if (!is_array($dependencies)) {
            $errors[] = 'dependencies doit être un tableau';
        }

        $load = $manifest['load'] ?? null;
        if (!is_array($load)) {
            $errors[] = 'load doit être un objet/tableau';
        }

        foreach (['routes.php', 'hooks.php', 'permissions.php', 'settings.php'] as $file) {
            $path = $modulePath . '/' . $file;
            if (is_file($path) && str_contains(realpath($path) ?: '', '..')) {
                $errors[] = 'Chemin dangereux détecté: ' . $file;
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}

