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
}
