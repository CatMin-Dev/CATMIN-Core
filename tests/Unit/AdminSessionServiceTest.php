<?php

namespace Tests\Unit;

use App\Services\AdminSessionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
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

        $current = $this->fakeRequest('sess-current');
        $otherA = $this->fakeRequest('sess-other-a');
        $otherB = $this->fakeRequest('sess-other-b');

        $service->registerSession($current, 7);
        $service->registerSession($otherA, 7);
        $service->registerSession($otherB, 7);

        $count = $service->revokeOthers(7, 'sess-current');

        $this->assertSame(2, $count);
        $this->assertFalse($service->isRevoked('sess-current'));
        $this->assertTrue($service->isRevoked('sess-other-a'));
        $this->assertTrue($service->isRevoked('sess-other-b'));
    }

    private function fakeRequest(string $sessionId): Request
    {
        $request = Request::create('/admin', 'GET');
        $session = new Store('test', new MockArraySessionStorage());
        $session->setId($sessionId);
        $session->start();
        $request->setLaravelSession($session);

        return $request;
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
