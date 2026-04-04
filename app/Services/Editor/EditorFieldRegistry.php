<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\Services\AddonManager;
use App\Services\ModuleManager;
use App\Support\Editor\EditorFieldDefinition;

class EditorFieldRegistry
{
    /**
     * @var array<int, callable(array<string,mixed>):array<int, array<string,mixed>|EditorFieldDefinition>>
     */
    private static array $providers = [];

    /**
     * @param callable(array<string,mixed>):array<int, array<string,mixed>|EditorFieldDefinition> $provider
     */
    public static function registerProvider(callable $provider): void
    {
        self::$providers[] = $provider;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function resolve(string $scope, string $field, array $context = []): ?EditorFieldDefinition
    {
        $needle = $scope . '.' . $field;

        foreach ($this->all($context) as $definition) {
            if (!$this->isAvailable($definition)) {
                continue;
            }

            $pattern = $definition->scope . '.' . $definition->field;
            if (fnmatch($pattern, $needle)) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $context
     * @return array<int, EditorFieldDefinition>
     */
    public function all(array $context = []): array
    {
        $definitions = [];

        foreach ((array) config('catmin_editor.field_definitions', []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $definitions[] = EditorFieldDefinition::fromArray(array_merge($item, ['source' => $item['source'] ?? 'core']));
        }

        foreach (self::$providers as $provider) {
            try {
                $provided = $provider($context);
            } catch (\Throwable) {
                $provided = [];
            }

            foreach ((array) $provided as $item) {
                if ($item instanceof EditorFieldDefinition) {
                    $definitions[] = $item;
                    continue;
                }
                if (is_array($item)) {
                    $definitions[] = EditorFieldDefinition::fromArray($item);
                }
            }
        }

        return $definitions;
    }

    public function isAvailable(EditorFieldDefinition $definition): bool
    {
        foreach ($definition->requiresModules as $module) {
            if (!ModuleManager::isEnabled($module)) {
                return false;
            }
        }

        foreach ($definition->requiresAddons as $addon) {
            $found = AddonManager::find($addon);
            if ($found === null || !((bool) ($found->enabled ?? false))) {
                return false;
            }
        }

        if ($definition->permissions !== [] && function_exists('catmin_can')) {
            $allowed = false;
            foreach ($definition->permissions as $permission) {
                if (catmin_can($permission)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return false;
            }
        }

        return true;
    }
}
