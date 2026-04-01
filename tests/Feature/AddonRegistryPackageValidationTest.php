<?php

namespace Tests\Feature;

use App\Services\AddonMarketplaceService;
use App\Services\AddonPackageValidatorService;
use App\Services\AddonRegistryService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class AddonRegistryPackageValidationTest extends TestCase
{
    private string $packagesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagesPath = AddonMarketplaceService::packagesPath();
        File::ensureDirectoryExists($this->packagesPath);
        File::ensureDirectoryExists(dirname(AddonRegistryService::registryPath()));
    }

    protected function tearDown(): void
    {
        foreach (['registry-addon-1.0.0-20260401-120000.zip', 'invalid-addon.zip'] as $file) {
            $path = $this->packagesPath . '/' . $file;
            if (File::exists($path)) {
                File::delete($path);
            }
            if (File::exists($path . '.sha256')) {
                File::delete($path . '.sha256');
            }
        }

        if (File::exists(AddonRegistryService::registryPath())) {
            File::delete(AddonRegistryService::registryPath());
        }

        parent::tearDown();
    }

    public function test_package_validator_accepts_valid_addon_package_and_registry_reads_it(): void
    {
        $archivePath = $this->packagesPath . '/registry-addon-1.0.0-20260401-120000.zip';
        $this->createZip($archivePath, [
            'addon.json' => json_encode([
                'name' => 'Registry Addon',
                'slug' => 'registry-addon',
                'description' => 'Addon de test registry',
                'version' => '1.0.0',
                'author' => 'CATMIN',
                'category' => 'integration',
                'enabled' => false,
                'dependencies' => [],
                'required_core_version' => '3.0.0',
                'required_php_version' => '8.2.0',
                'required_modules' => ['core'],
                'has_routes' => true,
                'has_migrations' => true,
                'has_assets' => true,
                'has_views' => true,
                'has_events' => false,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'routes.php' => "<?php\n",
            'Migrations/.gitkeep' => "\n",
            'Assets/js/addon.js' => "// test\n",
            'Views/admin/index.blade.php' => '<div>ok</div>',
            'Controllers/Admin/TestController.php' => "<?php\n",
            'Services/TestService.php' => "<?php\n",
        ]);

        $checksum = hash_file('sha256', $archivePath) ?: '';

        $validator = app(AddonPackageValidatorService::class);
        $validated = $validator->validateArchive($archivePath, $checksum);

        $this->assertTrue((bool) ($validated['ok'] ?? false));
        $this->assertSame('registry-addon', $validated['manifest']['slug'] ?? null);
        $this->assertSame('compatible', $validated['compatibility']['status'] ?? null);

        $registry = app(AddonRegistryService::class)->build();
        $entry = collect($registry['packages'] ?? [])->firstWhere('slug', 'registry-addon');

        $this->assertIsArray($entry);
        $this->assertSame('registry-addon', $entry['slug'] ?? null);
        $this->assertTrue((bool) ($entry['package_valid'] ?? false));
    }

    public function test_package_validator_rejects_invalid_package_without_manifest(): void
    {
        $archivePath = $this->packagesPath . '/invalid-addon.zip';
        $this->createZip($archivePath, [
            'README.md' => '# invalid',
        ]);

        $validated = app(AddonPackageValidatorService::class)->validateArchive($archivePath);

        $this->assertFalse((bool) ($validated['ok'] ?? false));
        $this->assertStringContainsString('addon.json', (string) ($validated['message'] ?? ''));
    }

    public function test_package_validator_blocks_package_with_missing_required_module(): void
    {
        $archivePath = $this->packagesPath . '/invalid-addon.zip';
        $this->createZip($archivePath, [
            'addon.json' => json_encode([
                'name' => 'Blocked Addon',
                'slug' => 'blocked-addon',
                'description' => 'Addon incompatible',
                'version' => '1.0.0',
                'author' => 'CATMIN',
                'category' => 'integration',
                'enabled' => false,
                'dependencies' => [],
                'required_core_version' => '3.0.0',
                'required_php_version' => '8.2.0',
                'required_modules' => ['core', 'missing-shop-module'],
                'has_routes' => true,
                'has_migrations' => false,
                'has_assets' => false,
                'has_views' => true,
                'has_events' => false,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'routes.php' => "<?php\n",
            'Views/admin/index.blade.php' => '<div>blocked</div>',
            'Controllers/Admin/TestController.php' => "<?php\n",
            'Services/TestService.php' => "<?php\n",
        ]);

        $validated = app(AddonPackageValidatorService::class)->validateArchive($archivePath);

        $this->assertFalse((bool) ($validated['ok'] ?? false));
        $this->assertSame('incompatible', $validated['compatibility']['status'] ?? null);
        $this->assertStringContainsString('missing-shop-module', implode(' ', $validated['compatibility']['blockers'] ?? []));
    }

    /**
     * @param array<string, string> $entries
     */
    private function createZip(string $path, array $entries): void
    {
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $entry => $content) {
            $zip->addFromString($entry, $content);
        }

        $zip->close();
    }
}
