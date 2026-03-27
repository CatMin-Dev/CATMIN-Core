<?php

namespace Modules\Logger\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Logger\Models\SystemLog;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $level = (string) $request->query('level', '');
        $channel = (string) $request->query('channel', '');

        $logs = SystemLog::query()
            ->when($level !== '', fn ($query) => $query->where('level', $level))
            ->when($channel !== '', fn ($query) => $query->where('channel', $channel))
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        return view()->file(base_path('modules/Logger/Views/index.blade.php'), [
            'currentPage' => 'logger',
            'logs' => $logs,
            'selectedLevel' => $level,
            'selectedChannel' => $channel,
            'levels' => ['info', 'warning', 'error'],
            'channels' => ['admin', 'application'],
        ]);
    }
}
