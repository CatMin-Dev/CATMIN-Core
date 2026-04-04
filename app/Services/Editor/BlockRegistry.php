<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\Services\AddonManager;
use App\Services\ModuleManager;

class BlockRegistry
{
    /**
     * @var array<int, callable(array<string,mixed>):array<int,array<string,mixed>>>
     */
    private static array $providers = [];

    /**
     * @param callable(array<string,mixed>):array<int,array<string,mixed>> $provider
     */
    public static function registerProvider(callable $provider): void
    {
        self::$providers[] = $provider;
    }

    /**
     * @param array<string,mixed> $context
     * @param array<int,string> $allowedIds
     * @return array<int,array<string,mixed>>
     */
    public function forContext(array $context = [], array $allowedIds = ['*']): array
    {
        $items = [];

        foreach ((array) config('catmin_editor.block_registry', []) as $block) {
            if (!$this->isAllowed($block, $allowedIds, $context)) {
                continue;
            }
            $items[] = $this->normalize($block, 'block');
        }

        foreach (self::$providers as $provider) {
            try {
                $provided = $provider($context);
            } catch (\Throwable) {
                $provided = [];
            }

            foreach ((array) $provided as $block) {
                if (!$this->isAllowed($block, $allowedIds, $context)) {
                    continue;
                }
                $items[] = $this->normalize($block, 'block');
            }
        }

        return array_values(array_filter($items, fn ($item) => trim((string) ($item['html'] ?? '')) !== ''));
    }

    /**
     * @param array<string,mixed> $item
     * @param array<int,string> $allowedIds
     * @param array<string,mixed> $context
     */
    private function isAllowed(array $item, array $allowedIds, array $context): bool
    {
        $id = (string) ($item['id'] ?? '');
        if ($id === '') {
            return false;
        }

        if (!in_array('*', $allowedIds, true) && !in_array($id, $allowedIds, true)) {
            return false;
        }

        $scope = (string) ($context['scope'] ?? '');
        $field = (string) ($context['field'] ?? '');
        $needle = $scope . '.' . $field;

        foreach ((array) ($item['scopes'] ?? ['*']) as $pattern) {
            if (fnmatch((string) $pattern, $needle)) {
                return $this->dependenciesOk($item) && $this->permissionOk($item);
            }
        }

        return false;
    }

    /** @param array<string,mixed> $item */
    private function dependenciesOk(array $item): bool
    {
        foreach ((array) ($item['requires_modules'] ?? []) as $module) {
            if (!ModuleManager::isEnabled((string) $module)) {
                return false;
            }
        }

        foreach ((array) ($item['requires_addons'] ?? []) as $addon) {
            $found = AddonManager::find((string) $addon);
            if ($found === null || !((bool) ($found->enabled ?? false))) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string,mixed> $item */
    private function permissionOk(array $item): bool
    {
        $permissions = (array) ($item['permissions'] ?? []);
        if ($permissions === [] || !function_exists('catmin_can')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (catmin_can((string) $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    private function normalize(array $item, string $type): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'type' => $type,
            'label' => (string) ($item['label'] ?? ''),
            'icon' => (string) ($item['icon'] ?? ''),
            'html' => (string) ($item['html'] ?? ''),
            'css' => (string) ($item['css'] ?? ''),
            'category' => (string) ($item['category'] ?? 'general'),
            'source' => (string) ($item['source'] ?? 'core'),
        ];
    }
}
