<?php

namespace Tests\Unit;

use App\Services\AdminSessionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminSessionServiceTest extends TestCase
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

    public function test_revoke_others_keeps_current_session(): void
    {
        $service = app(AdminSessionService::class);

        DB::table('admin_sessions')->insert([
            [
                'session_id' => 'sess-current',
                'admin_user_id' => 7,
                'last_activity_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_id' => 'sess-other-a',
                'admin_user_id' => 7,
                'last_activity_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'session_id' => 'sess-other-b',
                'admin_user_id' => 7,
                'last_activity_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $count = $service->revokeOthers(7, 'sess-current');

        $this->assertSame(2, $count);
        $this->assertFalse($service->isRevoked('sess-current'));
        $this->assertTrue($service->isRevoked('sess-other-a'));
        $this->assertTrue($service->isRevoked('sess-other-b'));
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('admin_sessions', function (Blueprint $table): void {
            $table->string('session_id', 128)->primary();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }
}
