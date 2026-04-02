<?php

namespace Tests\Feature;

use App\Services\AddonManager;
use App\Services\AddonMigrationRunner;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ArchitectureBalanceAndInstallFlowTest extends TestCase
{
    public function test_architecture_balance_command_returns_structured_report(): void
    {
        $exitCode = Artisan::call('catmin:architecture:balance', [
            '--json' => true,
        ]);

        $report = json_decode(Artisan::output(), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('ok', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('checks', $report);
        $this->assertArrayHasKey('metrics', $report);

        // Must remain clean over time; CI should fail if architecture boundaries regress.
        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) ($report['ok'] ?? false));
    }

    public function test_fresh_install_can_migrate_extensions_without_pending_addon_migrations(): void
    {
        $freshExit = Artisan::call('migrate:fresh', ['--force' => true]);
        $this->assertSame(0, $freshExit, 'migrate:fresh must succeed in integration flow.');

        $extensionsExit = Artisan::call('catmin:migrate:extensions', ['--addons' => true]);
        $this->assertSame(0, $extensionsExit, 'catmin:migrate:extensions --addons must succeed after fresh install.');

        AddonManager::clearCache();

        foreach (AddonManager::enabled() as $addon) {
            $slug = (string) ($addon->slug ?? '');
            if ($slug === '' || !((bool) ($addon->has_migrations ?? false))) {
                continue;
            }

            $this->assertFalse(
                AddonMigrationRunner::hasPending($slug),
                "Addon '{$slug}' still has pending migrations after fresh extension migration run."
            );
        }
    }
}
