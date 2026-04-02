<?php

namespace Tests\Feature;

use App\Services\AddonLoader;
use App\Services\AddonManager;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ShopAddonExtractionTest extends TestCase
{
    public function test_shop_is_discovered_as_addon(): void
    {
        AddonManager::clearCache();

        $this->assertTrue(AddonManager::exists('catmin-shop'));

        $addon = AddonManager::find('catmin-shop');

        $this->assertNotNull($addon);
        $this->assertTrue((bool) ($addon->enabled ?? false));
    }

    public function test_shop_routes_are_loaded_from_addon_namespace(): void
    {
        AddonManager::clearCache();
        AddonLoader::registerRoutes(app('router'));

        $route = Route::getRoutes()->getByName('admin.shop.manage');

        $this->assertNotNull($route);

        $action = (string) ($route?->getActionName() ?? '');

        $this->assertStringContainsString('Addons\\CatminShop\\Controllers\\Admin\\ProductController@index', $action);
        $this->assertTrue(class_exists(\Addons\CatminShop\Services\ShopAdminService::class));
        $this->assertTrue(class_exists(\Addons\CatminShop\Models\Product::class));
    }
}
