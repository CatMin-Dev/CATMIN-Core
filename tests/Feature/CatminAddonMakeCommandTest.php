<?php

namespace Tests\Feature;

use App\Services\AddonLoader;
use App\Services\AddonManager;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatminAddonMakeCommandTest extends TestCase
{
    private string $slug = 'scaffold-test-addon';

    private string $addonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addonPath = base_path('addons/' . $this->slug);

        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }

        AddonManager::clearCache();
    }

    protected function tearDown(): void
    {
        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }

        AddonManager::clearCache();

        parent::tearDown();
    }

    public function test_addon_generator_creates_detectable_and_routable_addon(): void
    {
        $this->artisan('catmin:addon:make', [
            'name' => 'Scaffold Test Addon',
            'slug' => $this->slug,
            '--description' => 'Addon de test automatique',
            '--addon-version' => '1.2.3',
            '--depends' => 'core,logger',
            '--category' => 'integration',
            '--enable' => true,
        ])->assertExitCode(0);

        $this->assertFileExists($this->addonPath . '/addon.json');
        $this->assertFileExists($this->addonPath . '/routes.php');
        $this->assertFileExists($this->addonPath . '/Controllers/Admin/ScaffoldTestAddonAdminController.php');
        $this->assertFileExists($this->addonPath . '/Views/admin/index.blade.php');
        $this->assertFileExists($this->addonPath . '/Docs/README.md');

        $manifest = json_decode((string) File::get($this->addonPath . '/addon.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('3.0.0', $manifest['required_core_version'] ?? null);
        $this->assertSame('8.2.0', $manifest['required_php_version'] ?? null);
        $this->assertSame(['core', 'logger'], $manifest['required_modules'] ?? []);
        $this->assertSame([], $manifest['dependencies'] ?? []);
        $this->assertTrue((bool) ($manifest['has_routes'] ?? false));
        $this->assertTrue((bool) ($manifest['has_migrations'] ?? false));
        $this->assertTrue((bool) ($manifest['has_assets'] ?? false));
        $this->assertTrue((bool) ($manifest['has_views'] ?? false));
        $this->assertArrayHasKey('entrypoints', $manifest);
        $this->assertArrayHasKey('permissions_declared', $manifest);

        AddonManager::clearCache();
        $this->assertTrue(AddonManager::exists($this->slug));
        $this->assertTrue((bool) (AddonManager::canEnable($this->slug)['allowed'] ?? false));

        AddonLoader::registerRoutes(app('router'));

        $adminPath = trim((string) config('catmin.admin.path', 'admin'), '/');
        $response = $this->withoutMiddleware()->get('/' . $adminPath . '/addons/' . $this->slug);

        $response->assertOk();
        $response->assertSee('Scaffold Test Addon');
    }
}
