<?php

namespace Tests\Unit\Dashboard;

use App\Services\Dashboard\DashboardKpiService;
use App\Services\MonitoringService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->bindFakeMonitoringService(88);
    }

    public function test_widgets_are_conditional_for_optional_addons(): void
    {
        $serviceWithoutAddons = $this->makeService([]);
        $dashboardWithout = $serviceWithoutAddons->build();
        $widgetIdsWithout = collect((array) ($dashboardWithout['widgets'] ?? []))->pluck('id')->values()->all();

        $this->assertNotContains('shop-orders', $widgetIdsWithout);
        $this->assertNotContains('low-stock', $widgetIdsWithout);
        $this->assertNotContains('event-activity', $widgetIdsWithout);

        $serviceWithAddons = $this->makeService(['catmin-shop', 'cat-event']);
        $dashboardWith = $serviceWithAddons->build();
        $widgetIdsWith = collect((array) ($dashboardWith['widgets'] ?? []))->pluck('id')->values()->all();

        $this->assertContains('shop-orders', $widgetIdsWith);
        $this->assertContains('low-stock', $widgetIdsWith);
        $this->assertContains('event-activity', $widgetIdsWith);
    }

    public function test_kpi_values_are_computed_from_real_data(): void
    {
        $this->ensurePagesSchema();
        $this->ensureArticlesSchema();
        $this->ensureMonitoringIncidentsSchema();

        DB::table('pages')->insert([
            ['title' => 'P1', 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'P2', 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'P3', 'status' => 'draft', 'published_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('articles')->insert([
            ['title' => 'A1', 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'A2', 'status' => 'draft', 'published_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('monitoring_incidents')->insert([
            ['status' => 'critical', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'warning', 'created_at' => now(), 'updated_at' => now()],
            ['status' => 'recovered', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $service = $this->makeService([]);
        $dashboard = $service->build();

        $this->assertSame(3, (int) ($dashboard['kpi_index']['content_total_published'] ?? 0));
        $this->assertSame(2, (int) ($dashboard['kpi_index']['incidents_open'] ?? 0));
        $this->assertSame(88, (int) ($dashboard['kpi_index']['perf_score'] ?? 0));
    }

    public function test_dashboard_is_stable_without_optional_addon_tables(): void
    {
        $service = $this->makeService([]);
        $dashboard = $service->build();

        $this->assertIsArray($dashboard);
        $this->assertIsArray($dashboard['kpis'] ?? null);
        $this->assertIsArray($dashboard['widgets'] ?? null);
        $this->assertIsArray($dashboard['charts'] ?? null);
    }

    private function makeService(array $enabledAddons): DashboardKpiService
    {
        return new class($enabledAddons) extends DashboardKpiService {
            /**
             * @param array<int, string> $enabledAddons
             */
            public function __construct(private array $enabledAddons)
            {
            }

            protected function isAddonEnabled(string $slug): bool
            {
                $target = strtolower($slug);
                foreach ($this->enabledAddons as $enabledAddon) {
                    if (strtolower($enabledAddon) === $target) {
                        return true;
                    }
                }

                return false;
            }
        };
    }

    private function bindFakeMonitoringService(int $score): void
    {
        app()->instance(MonitoringService::class, new class($score) extends MonitoringService {
            public function __construct(private int $score)
            {
            }

            public function buildDashboardReport(int $limitIncidents = 20): array
            {
                return [
                    'global' => ['status' => 'ok', 'score' => $this->score],
                    'health_score' => [
                        'score' => $this->score,
                        'label' => 'Stable',
                        'confidence' => 100,
                        'recommendations' => [],
                        'trend' => ['delta' => 0, 'message' => 'Stable'],
                    ],
                    'incidents' => [],
                    'checks' => [],
                    'history' => [],
                ];
            }
        });
    }

    private function ensurePagesSchema(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    private function ensureArticlesSchema(): void
    {
        if (Schema::hasTable('articles')) {
            return;
        }

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    private function ensureMonitoringIncidentsSchema(): void
    {
        if (Schema::hasTable('monitoring_incidents')) {
            return;
        }

        Schema::create('monitoring_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 32)->default('warning');
            $table->timestamps();
        });
    }
}
