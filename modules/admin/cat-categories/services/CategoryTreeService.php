<?php

declare(strict_types=1);

namespace Modules\CatCategories\services;

final class CategoryTreeService
{
    public function build(array $rows): array
    {
        $byParent = [];
        foreach ($rows as $row) {
            $pid = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
            $byParent[$pid][] = $row;
        }

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent): array {
            $nodes = [];
            foreach ($byParent[$parentId] ?? [] as $row) {
                $id = (int) ($row['id'] ?? 0);
                $children = $walk($id, $depth + 1);
                $row['depth'] = $depth;
                $row['children'] = $children;
                $nodes[] = $row;
            }
            return $nodes;
        };

        return $walk(0, 0);
    }

    public function flattenForSelect(array $tree): array
    {
        $out = [];
        $walk = function (array $nodes) use (&$walk, &$out): void {
            foreach ($nodes as $node) {
                $depth = (int) ($node['depth'] ?? 0);
                $prefix = str_repeat('-- ', max(0, $depth));
                $out[] = [
                    'id' => (int) ($node['id'] ?? 0),
                    'name' => $prefix . (string) ($node['name'] ?? ''),
                    'depth' => $depth,
                    'usage_count' => (int) ($node['usage_count'] ?? 0),
                ];
                $walk((array) ($node['children'] ?? []));
            }
        };
        $walk($tree);
        return $out;
    }
}
