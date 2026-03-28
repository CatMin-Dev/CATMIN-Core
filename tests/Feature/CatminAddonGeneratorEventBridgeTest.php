<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatminAddonGeneratorEventBridgeTest extends TestCase
{
    private string $slug = 'bridge-addon-test';

    private string $addonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addonPath = base_path('addons/' . $this->slug);

        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }

        parent::tearDown();
    }

    public function test_generator_can_create_event_bridge_ready_addon(): void
    {
        $this->artisan('catmin:addon:make', [
            'name' => 'Bridge Addon Test',
            'slug' => $this->slug,
            '--addon-version' => '1.0.0',
            '--with-events' => true,
            '--with-ui-hooks' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists($this->addonPath . '/Events/BridgeAddonTestConfiguredEvent.php');
        $this->assertFileExists($this->addonPath . '/Listeners/LogBridgeAddonTestConfiguredListener.php');
        $this->assertFileExists($this->addonPath . '/hooks.php');

        $manifest = json_decode((string) File::get($this->addonPath . '/addon.json'), true);
        $this->assertIsArray($manifest);
        $this->assertContains('addon.bridge_addon_test.configured', $manifest['events_emitted'] ?? []);
        $this->assertContains('setting.updated', $manifest['events_listens'] ?? []);
        $this->assertContains('after:admin.topbar', $manifest['ui_hooks'] ?? []);

        $hooksPhp = (string) File::get($this->addonPath . '/hooks.php');
        $this->assertStringContainsString("CatminEventBus::listen(CatminEventBus::SETTING_UPDATED", $hooksPhp);
        $this->assertStringContainsString("CatminHookRegistry::after('admin.topbar'", $hooksPhp);

        $docs = (string) File::get($this->addonPath . '/Docs/README.md');
        $this->assertStringContainsString('Events emis', $docs);
        $this->assertStringContainsString('Hooks UI', $docs);
    }
}
