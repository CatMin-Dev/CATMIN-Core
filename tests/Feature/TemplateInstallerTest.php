<?php

namespace Tests\Feature;

use App\Services\TemplateInstallerService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TemplateInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);

        $moduleMigrations = [
            'pages' => 'modules/Pages/Migrations',
            'articles' => 'modules/Articles/Migrations',
            'menus' => 'modules/Menus/Migrations',
            'blocks' => 'modules/Blocks/Migrations',
            'media_assets' => 'modules/Media/Migrations',
            'settings' => 'modules/Settings/Migrations',
        ];

        foreach ($moduleMigrations as $table => $path) {
            if (!Schema::hasTable($table)) {
                $this->artisan('migrate', ['--force' => true, '--path' => $path]);
            }
        }
    }

    public function test_install_template_minimal(): void
    {
        $result = app(TemplateInstallerService::class)->installFromSlug('minimal', [
            'overwrite' => true,
            'source' => 'test',
        ]);

        $this->assertTrue((bool) ($result['ok'] ?? false), json_encode($result));
        $this->assertDatabaseHas('pages', ['slug' => 'accueil']);
        $this->assertDatabaseHas('menu_items', ['label' => 'Contact']);
        $this->assertDatabaseHas('blocks', ['slug' => 'footer-baseline']);
        $this->assertDatabaseHas('settings', ['key' => 'site_name']);
        $this->assertDatabaseHas('media_assets', ['path' => 'media/placeholders/hero-minimal.jpg']);
    }

    public function test_install_template_core_full(): void
    {
        $result = app(TemplateInstallerService::class)->installFromSlug('core-full', [
            'overwrite' => true,
            'source' => 'test',
        ]);

        $this->assertTrue((bool) ($result['ok'] ?? false), json_encode($result));
        $this->assertDatabaseHas('pages', ['slug' => 'a-propos']);
        $this->assertDatabaseHas('articles', ['slug' => 'guide-prise-en-main']);
        $this->assertDatabaseHas('menus', ['slug' => 'menu-principal']);
        $this->assertDatabaseHas('settings', ['key' => 'shop_currency']);
    }

    public function test_export_template(): void
    {
        app(TemplateInstallerService::class)->installFromSlug('minimal', [
            'overwrite' => true,
            'source' => 'test',
        ]);

        $path = storage_path('app/tests/exported-template.template.json');
        File::ensureDirectoryExists(dirname($path));
        File::delete($path);

        $result = app(TemplateInstallerService::class)->exportToFile('test-export', $path, [
            'name' => 'Test Export',
            'description' => 'Template exporte durant les tests',
        ]);

        $this->assertTrue((bool) ($result['ok'] ?? false), json_encode($result));
        $this->assertFileExists($path);

        $decoded = json_decode((string) File::get($path), true);
        $this->assertIsArray($decoded);
        $this->assertSame('test-export', $decoded['slug'] ?? null);
        $this->assertIsArray($decoded['payload']['pages'] ?? null);
    }

    public function test_validate_template_invalide(): void
    {
        $result = app(TemplateInstallerService::class)->validateTemplate([
            'name' => 'Invalid template',
            'version' => '1.0.0',
            'payload' => [
                'pages' => [
                    ['title' => '', 'slug' => ''],
                ],
            ],
        ]);

        $this->assertFalse((bool) ($result['ok'] ?? true));
        $this->assertNotEmpty((array) ($result['errors'] ?? []));
    }
}
