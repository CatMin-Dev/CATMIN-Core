<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class QueueAdminActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_index_denies_without_list_permission(): void
    {
        $response = $this->withAdminPermissions([])->get($this->adminPath('/queue'));

        $response->assertForbidden();
    }

    public function test_queue_index_allows_with_list_permission(): void
    {
        $response = $this->withAdminPermissions(['module.queue.list'])->get($this->adminPath('/queue'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_retry_selected_denies_without_config_permission(): void
    {
        $failedId = $this->insertFailedJob();

        $response = $this->withAdminPermissions(['module.queue.list'])
            ->post($this->adminPath('/queue/failed/retry-selected'), ['ids' => [$failedId]]);

        $response->assertForbidden();
    }

    public function test_retry_selected_retries_selected_failed_jobs(): void
    {
        $firstId = $this->insertFailedJob('App\\Jobs\\SendNewsletter');
        $secondId = $this->insertFailedJob('App\\Jobs\\SyncInventory');

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', \Mockery::on(function (array $payload): bool {
                return isset($payload['id']) && count((array) $payload['id']) === 2;
            }))
            ->andReturn(0);

        $response = $this->withAdminPermissions(['module.queue.config'])
            ->post($this->adminPath('/queue/failed/retry-selected'), ['ids' => [$firstId, $secondId]]);

        $response->assertRedirect($this->adminPath('/queue'));
        $response->assertSessionHas('success');
    }

    public function test_clear_selected_deletes_only_selected_failed_jobs(): void
    {
        $firstId = $this->insertFailedJob('App\\Jobs\\DeleteCache');
        $secondId = $this->insertFailedJob('App\\Jobs\\ProcessMedia');

        $response = $this->withAdminPermissions(['module.queue.config'])
            ->post($this->adminPath('/queue/failed/selected'), ['ids' => [$firstId]]);

        $response->assertRedirect($this->adminPath('/queue'));
        $this->assertDatabaseMissing('failed_jobs', ['id' => $firstId]);
        $this->assertDatabaseHas('failed_jobs', ['id' => $secondId]);
    }

    public function test_job_detail_requires_list_permission(): void
    {
        $failedId = $this->insertFailedJob('App\\Jobs\\ReportDailyMetrics');

        $response = $this->withAdminPermissions([])->get($this->adminPath('/queue/jobs/failed/' . $failedId));

        $response->assertForbidden();
    }

    public function test_job_detail_is_accessible_with_list_permission(): void
    {
        $failedId = $this->insertFailedJob('App\\Jobs\\ReportDailyMetrics');

        $response = $this->withAdminPermissions(['module.queue.list'])->get($this->adminPath('/queue/jobs/failed/' . $failedId));

        $response->assertOk();
        $response->assertSee('Détail job queue', false);
        $response->assertSee('ReportDailyMetrics', false);
    }

    private function insertFailedJob(string $displayName = 'App\\Jobs\\DemoJob'): int
    {
        return (int) DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => $displayName,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'attempts' => 1,
                'token' => 'secret-token',
            ]),
            'exception' => 'RuntimeException: test failure in queue worker',
            'failed_at' => now(),
        ]);
    }

    private function withAdminPermissions(array $permissions): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'rbac-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => [],
        ]);
    }

    private function adminPath(string $path): string
    {
        return '/' . trim((string) config('catmin.admin.path', 'admin'), '/') . $path;
    }
}
