<?php

namespace Modules\Cron\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cron\Services\CronService;

class CronController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('modules/Cron/Views/index.blade.php'), [
            'currentPage' => 'cron',
            'logs' => CronService::recentLogs(50),
            'tasks' => CronService::availableTasks(),
            'customTasks' => CronService::customTasks(),
            'frequencies' => CronService::frequencyOptions(),
        ]);
    }

    public function runTask(Request $request, string $task): RedirectResponse
    {
        $available = CronService::availableTasks();

        if (!array_key_exists($task, $available)) {
            return redirect()->route('admin.cron.index')
                ->with('error', 'Tâche inconnue.');
        }

        CronService::runTask($task);

        return redirect()->route('admin.cron.index')
            ->with('success', "Tâche « {$available[$task]} » déclenchée manuellemement.");
    }

    public function storeCustomTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'command' => ['required', 'string', 'max:160'],
            'frequency' => ['required', 'in:' . implode(',', array_keys(CronService::frequencyOptions()))],
            'scope' => ['required', 'in:site,module'],
            'module' => ['nullable', 'string', 'max:80'],
        ]);

        CronService::addCustomTask([
            'label' => (string) $validated['label'],
            'description' => (string) ($validated['description'] ?? ''),
            'command' => (string) $validated['command'],
            'frequency' => (string) $validated['frequency'],
            'scope' => (string) $validated['scope'],
            'module' => (string) ($validated['module'] ?? ''),
            'enabled' => true,
        ]);

        return redirect()->route('admin.cron.index')
            ->with('success', 'Tache cron personnalisee ajoutee.');
    }

    public function deleteCustomTask(string $taskId): RedirectResponse
    {
        CronService::removeCustomTask($taskId);

        return redirect()->route('admin.cron.index')
            ->with('success', 'Tache cron personnalisee supprimee.');
    }
}
