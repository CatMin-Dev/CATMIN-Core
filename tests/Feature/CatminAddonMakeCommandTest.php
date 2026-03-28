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
