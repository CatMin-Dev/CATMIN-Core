<?php

namespace Tests\Unit\Performance;

use App\Services\Performance\PerformanceReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Modules\Logger\Models\SystemLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();

        config()->set('catmin.performance.budgets', [
            [
                'key' => 'admin.dashboard',
                'label' => 'Dashboard home',
                'route' => 'admin.index',
                'target_response_ms' => 350,
                'max_response_ms' => 700,
                'max_queries' => 12,
                'max_slow_queries' => 0,
            ],
        ]);
    }

    #[Test]
    public function it_builds_route_budget_and_slow_query_summary(): void
    {
        SystemLog::query()->create([
            'channel' => 'performance',
            'level' => 'warning',
            'event' => 'http.request.performance',
            'message' => 'HTTP request performance',
            'context' => [
                'route_name' => 'admin.index',
                'path' => 'admin',
                'duration_ms' => 820,
                'query_count' => 18,
                'slow_query_count' => 1,
                'is_slow_request' => true,
                'is_budget_breach' => true,
                'budget' => ['key' => 'admin.dashboard'],
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        SystemLog::query()->create([
            'channel' => 'performance',
            'level' => 'warning',
            'event' => 'db.query.slow',
            'message' => 'Slow database query detected',
            'context' => [
                'sql' => 'select * from pages where status = ?',
                'time_ms' => 310,
                'route_name' => 'admin.index',
                'path' => 'admin',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(PerformanceReportService::class)->buildReport(24);

        $this->assertSame(1, $report['summary']['requests_profiled']);
        $this->assertSame(1, $report['summary']['budget_breaches']);
        $this->assertCount(1, $report['budgets']);
        $this->assertSame('Dashboard home', $report['budgets'][0]['label']);
        $this->assertSame(1, $report['budgets'][0]['breaches']);
        $this->assertCount(1, $report['slow_queries_top']);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 32)->nullable();
            $table->string('level', 32)->nullable();
            $table->string('event', 128)->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->string('admin_username')->nullable();
            $table->string('method', 16)->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->smallInteger('status_code')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}