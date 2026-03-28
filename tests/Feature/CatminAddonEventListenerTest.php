<?php

namespace Tests\Feature;

use App\Services\AddonManager;
use App\Services\CatminEventBus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatminAddonEventListenerTest extends TestCase
{
    private string $slug = 'event-listener-addon-test';

    private string $addonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addonPath = base_path('addons/' . $this->slug);

        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }

        File::ensureDirectoryExists($this->addonPath);
        File::put($this->addonPath . '/addon.json', json_encode([
            'name' => 'Event Listener Addon Test',
            'slug' => $this->slug,
            'version' => '1.0.0',
            'enabled' => true,
            'requires_core' => true,
            'depends_modules' => ['core'],
            'description' => 'Addon temporaire pour test event listener',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put($this->addonPath . '/hooks.php', <<<'PHP'
<?php

use App\Services\CatminEventBus;
use Illuminate\Support\Facades\Cache;

CatminEventBus::listen(CatminEventBus::SETTING_UPDATED, function (array $payload): void {
    Cache::put('catmin_event_listener_test_hit', json_encode($payload), 60);
});
PHP
);

        AddonManager::clearCache();
        Cache::forget('catmin_event_listener_test_hit');
    }

    protected function tearDown(): void
    {
        Cache::forget('catmin_event_listener_test_hit');

        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }

        AddonManager::clearCache();

        parent::tearDown();
    }

    public function test_enabled_addon_can_listen_to_real_event(): void
    {
        require_once $this->addonPath . '/hooks.php';

        CatminEventBus::dispatch(CatminEventBus::SETTING_UPDATED, [
            'setting' => ['key' => 'site.name', 'value' => 'CATMIN'],
        ]);

        $captured = (string) Cache::get('catmin_event_listener_test_hit', '');

        $this->assertNotSame('', $captured);
        $this->assertStringContainsString('site.name', $captured);
    }
}
