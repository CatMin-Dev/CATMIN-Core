<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\services;

final class BreadcrumbSeedService
{
    public function snippet(string $menuKey = 'main_nav'): string
    {
        $menuKey = trim($menuKey) !== '' ? trim($menuKey) : 'main_nav';
        return "SELECT * FROM mod_cat_menu_link_items WHERE menu_key = '" . addslashes($menuKey) . "' ORDER BY sort_order ASC;";
    }
}
