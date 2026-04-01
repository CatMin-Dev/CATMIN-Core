<?php

namespace Tests\Feature;

use App\Services\AddonManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatminExtensionContractsCommandTest extends TestCase
{
    private string $badAddonSlug = 'contract-bad-addon';

    private string $goodAddonSlug = 'contract-good-addon';

    protected function tearDown(): void
    {
        foreach ([$this->badAddonSlug, $this->goodAddonSlug] as $slug) {
            $path = base_path('addons/' . $slug);
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }

        AddonManager::clearCache();

        parent::tearDown();
    }

    public function test_validate_extension_users_module_passes(): void
    {
        $exitCode = Artisan::call('catmin:validate-extension', [
            'slug' => 'users',
            '--json' => true,
        ]);

        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($report);
        $this->assertTrue((bool) ($report['ok'] ?? false));
        $this->assertSame('module', $report['type'] ?? null);
        $this->assertSame('users', $report['slug'] ?? null);
    }

    public function test_validate_addon_detects_invalid_structure(): void
    {
        $path = base_path('addons/' . $this->badAddonSlug);
        File::ensureDirectoryExists($path);

        File::put($path . '/addon.json', json_encode([
            'name' => 'Bad Contract Addon',
            'slug' => $this->badAddonSlug,
            'version' => '1.0.0',
            'enabled' => false,
            'requires_core' => true,
            'required_modules' => ['core'],
            'has_routes' => true,
            'has_migrations' => true,
            'has_views' => true,
            'has_assets' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        AddonManager::clearCache();

        $exitCode = Artisan::call('catmin:validate-addon', [
            'slug' => $this->badAddonSlug,
            '--json' => true,
        ]);

        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertIsArray($report);
        $this->assertFalse((bool) ($report['ok'] ?? true));
        $this->assertNotEmpty($report['errors'] ?? []);
    }

    public function test_validate_addon_passes_for_contract_compliant_addon(): void
    {
        $path = base_path('addons/' . $this->goodAddonSlug);
        File::ensureDirectoryExists($path . '/Controllers');
        File::ensureDirectoryExists($path . '/Views');
        File::ensureDirectoryExists($path . '/Services');
        File::ensureDirectoryExists($path . '/Migrations');
        File::ensureDirectoryExists($path . '/Docs');

        File::put($path . '/addon.json', json_encode([
            'name' => 'Good Contract Addon',
            'slug' => $this->goodAddonSlug,
            'version' => '1.0.0',
            'enabled' => false,
            'requires_core' => true,
            'required_modules' => ['core'],
            'has_routes' => true,
            'has_migrations' => true,
            'has_views' => true,
            'has_assets' => false,
            'permissions_declared' => ['module.logger.list'],
            'ui_hooks' => ['after:admin.topbar'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put($path . '/routes.php', <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.contract_good_addon.')
    ->group(function () {
        Route::get('/contract-good-addon', function () {
            return 'ok';
        })->middleware('catmin.permission:module.logger.list')->name('index');
    });
PHP
);

        File::put($path . '/Docs/README.md', "# Good Contract Addon\n");

        AddonManager::clearCache();

        $exitCode = Artisan::call('catmin:validate-addon', [
            'slug' => $this->goodAddonSlug,
            '--json' => true,
        ]);

        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($report);
        $this->assertTrue((bool) ($report['ok'] ?? false));
        $this->assertSame('addon', $report['type'] ?? null);
    }
}
