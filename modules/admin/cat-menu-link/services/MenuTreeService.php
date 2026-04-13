<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\services;

final class MenuTreeService
{
    public function tree(array $items): array
    {
        $indexed = [];
        foreach ($items as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $row['children'] = [];
            $indexed[$id] = $row;
        }

        $roots = [];
        foreach ($indexed as $id => $row) {
            $parentId = (int) ($row['parent_item_id'] ?? 0);
            if ($parentId > 0 && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$indexed[$id];
            } else {
                $roots[] = &$indexed[$id];
            }
        }

        return $roots;
    }
}
