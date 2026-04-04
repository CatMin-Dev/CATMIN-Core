<?php

namespace App\Services\AdminNavigation;

class AdminNavigationStateService
{
    /**
     * @param array<int,array<string,mixed>> $tree
     * @return array<int,array<string,mixed>>
     */
    public function apply(array $tree): array
    {
        $routeName = (string) request()->route()?->getName();
        $routeName = str_starts_with($routeName, 'admin.') ? substr($routeName, 6) : $routeName;
        $module = (string) request()->route('module', '');

        return collect($tree)->map(function (array $master) use ($routeName, $module): array {
            $masterActive = false;

            $master['children'] = collect((array) ($master['children'] ?? []))
                ->map(function (array $sub) use ($routeName, $module, &$masterActive): array {
                    $subActive = false;

                    $sub['children'] = collect((array) ($sub['children'] ?? []))
                        ->map(function (array $leaf) use ($routeName, $module, &$subActive, &$masterActive): array {
                            $active = $this->isLeafActive($leaf, $routeName, $module);
                            $leaf['active'] = $active;
                            if ($active) {
                                $subActive = true;
                                $masterActive = true;
                            }
                            return $leaf;
                        })
                        ->values()
                        ->all();

                    $sub['active'] = $subActive;
                    $sub['opened'] = $subActive;
                    return $sub;
                })
                ->values()
                ->all();

            $master['active'] = $masterActive;
            $master['opened'] = $masterActive;

            return $master;
        })->values()->all();
    }

    /** @param array<string,mixed> $leaf */
    private function isLeafActive(array $leaf, string $routeName, string $module): bool
    {
        $leafRoute = (string) ($leaf['route'] ?? '');
        if ($leafRoute !== '') {
            $leafRoute = str_starts_with($leafRoute, 'admin.') ? substr($leafRoute, 6) : $leafRoute;
            if ($leafRoute === $routeName) {
                if (!empty($leaf['match_module'])) {
                    return (string) $leaf['match_module'] === $module;
                }
                return true;
            }
        }

        foreach ((array) ($leaf['active_when'] ?? []) as $pattern) {
            $p = (string) $pattern;
            if ($p === 'content.show' && $routeName === 'content.show' && !empty($leaf['match_module'])) {
                if ((string) $leaf['match_module'] === $module) {
                    return true;
                }
            }

            if (request()->routeIs('admin.' . $p)) {
                if ($routeName === 'content.show' && !empty($leaf['match_module'])) {
                    return (string) $leaf['match_module'] === $module;
                }
                return true;
            }
        }

        return false;
    }
}
