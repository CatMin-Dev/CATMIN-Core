<?php

namespace Modules\Queue\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Queue\Services\QueueAdminService;

class QueueController extends Controller
{
    public function __construct(private readonly QueueAdminService $queueService)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', 'all'),
            'queue' => (string) $request->query('queue', ''),
            'q' => (string) $request->query('q', ''),
        ];

        if (!in_array($filters['status'], ['all', 'failed', 'pending'], true)) {
            $filters['status'] = 'all';
        }

        $perPage = $this->queueService->failedJobsLimit();
        $counters = $this->queueService->counters();

        $failedJobs = $this->queueService->failedJobs($filters, $perPage);
        $pendingJobs = $this->queueService->pendingJobs($filters, $perPage);

        return view('module_queue::index', [
            'currentPage' => 'queue',
            'pending' => $counters['pending'],
            'failed' => $counters['failed'],
            'connection' => config('queue.default', 'sync'),
            'failedJobs' => $failedJobs,
            'pendingJobs' => $pendingJobs,
            'filters' => $filters,
            'queues' => $this->queueService->availableQueues(),
        ]);
    }

    public function showJob(string $source, int $id): View|RedirectResponse
    {
        $job = $source === 'failed'
            ? $this->queueService->failedDetail($id)
            : $this->queueService->pendingDetail($id);

        if (!$job) {
            return redirect()->route('admin.queue.index')
                ->with('error', 'Job introuvable.');
        }

        return view('module_queue::show', [
            'currentPage' => 'queue',
            'job' => $job,
        ]);
    }

    public function deleteFailedJob(int $id): RedirectResponse
    {
        try {
            $deleted = $this->queueService->deleteFailedIds([$id]);
            if ($deleted < 1) {
                return redirect()->route('admin.queue.index')->with('error', 'Job introuvable ou deja supprime.');
            }
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Suppression échouée.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', 'Job supprimé.');
    }

    public function retryFailedJob(int $id): RedirectResponse
    {
        try {
            $retried = $this->queueService->retryFailedIds([$id]);
            if ($retried < 1) {
                return redirect()->route('admin.queue.index')->with('error', 'Aucun job relance.');
            }
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Relance échouée.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', 'Job relancé.');
    }

    public function retrySelectedFailed(Request $request): RedirectResponse
    {
        $ids = $this->extractIds($request);
        if ($ids === []) {
            return redirect()->route('admin.queue.index')->with('error', 'Selection vide: aucun job relance.');
        }

        try {
            $retried = $this->queueService->retryFailedIds($ids);
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Relance selectionnee echouee.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', $retried . ' job(s) relance(s).');
    }

    public function retryAllFailed(): RedirectResponse
    {
        try {
            $ids = $this->queueService->allFailedIds();

            if ($ids === []) {
                return redirect()->route('admin.queue.index')->with('success', 'Aucun job à relancer.');
            }

            $this->queueService->retryFailedIds($ids);
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Relance globale échouée.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', 'Tous les jobs en échec ont été relancés.');
    }

    public function clearSelectedFailed(Request $request): RedirectResponse
    {
        $ids = $this->extractIds($request);
        if ($ids === []) {
            return redirect()->route('admin.queue.index')->with('error', 'Selection vide: aucun job supprime.');
        }

        try {
            $deleted = $this->queueService->deleteFailedIds($ids);
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Suppression selectionnee echouee.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', $deleted . ' job(s) en echec supprime(s).');
    }

    public function clearFailed(): RedirectResponse
    {
        try {
            $this->queueService->clearFailed();
        } catch (\Throwable) {
            return redirect()->route('admin.queue.index')->with('error', 'Nettoyage échoué.');
        }

        return redirect()->route('admin.queue.index')
            ->with('success', 'Tous les jobs en échec ont été supprimés.');
    }

    /**
     * @return array<int, int>
     */
    private function extractIds(Request $request): array
    {
        return collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
