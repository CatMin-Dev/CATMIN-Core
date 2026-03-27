<?php

namespace Modules\Queue\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(): View
    {
        $pending = $this->safeDatabaseCount('jobs');
        $failed  = $this->safeDatabaseCount('failed_jobs');

        $failedJobs = $this->safeFailedJobs();

        return view()->file(base_path('modules/Queue/Views/index.blade.php'), [
            'currentPage' => 'queue',
            'pending' => $pending,
            'failed' => $failed,
            'connection' => config('queue.default', 'sync'),
            'failedJobs' => $failedJobs,
        ]);
    }

    public function deleteFailedJob(int $id): RedirectResponse
    {
        try {
            DB::table('failed_jobs')->where('id', $id)->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Suppression échouée.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', 'Job supprimé.');
    }

    public function clearFailed(): RedirectResponse
    {
        try {
            DB::table('failed_jobs')->truncate();
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Nettoyage échoué.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', 'Tous les jobs en échec ont été supprimés.');
    }

    private function safeDatabaseCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safeFailedJobs(): \Illuminate\Support\Collection
    {
        try {
            return DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(20)
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}
