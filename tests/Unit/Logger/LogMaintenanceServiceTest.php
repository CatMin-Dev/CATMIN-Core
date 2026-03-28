<?php

namespace Tests\Unit\Logger;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Logger\Services\LogMaintenanceService;
use Tests\TestCase;

class LogMaintenanceServiceTest extends TestCase
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
    }

    public function test_purge_by_level_deletes_only_matching_rows(): void
    {
        DB::table('system_logs')->insert([
            ['channel' => 'webhooks', 'level' => 'error', 'event' => 'a', 'message' => 'x', 'created_at' => now(), 'updated_at' => now()],
            ['channel' => 'webhooks', 'level' => 'info', 'event' => 'b', 'message' => 'y', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $service = app(LogMaintenanceService::class);
        $deleted = $service->purge(['level' => 'error']);

        $this->assertSame(1, $deleted);
        $this->assertSame(1, DB::table('system_logs')->count());
    }

    public function test_rotate_daily_moves_old_logs_to_archive(): void
    {
        DB::table('system_logs')->insert([
            'channel' => 'application',
            'level' => 'error',
            'event' => 'exception.reported',
            'message' => 'boom',
            'context' => json_encode(['a' => 1]),
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        $service = app(LogMaintenanceService::class);
        $result = $service->rotateDaily(14, 90);

        $this->assertSame(1, (int) ($result['archived'] ?? 0));
        $this->assertSame(0, DB::table('system_logs')->count());
        $this->assertSame(1, DB::table('system_logs_archive')->count());
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

        Schema::create('system_logs_archive', function (Blueprint $table): void {
            $table->id();
            $table->date('archive_date')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('level', 20)->nullable();
            $table->string('event', 100)->nullable();
            $table->longText('message')->nullable();
            $table->json('context')->nullable();
            $table->string('admin_username', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('log_count')->default(1);
            $table->timestamp('created_at')->nullable();
        });
    }
}
