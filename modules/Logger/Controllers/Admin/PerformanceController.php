<?php

namespace Modules\Logger\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Performance\PerformanceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cache\Services\QueryCacheService;

class PerformanceController extends Controller
{
    public function __construct(private readonly PerformanceReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        $hours = max(1, min(168, (int) $request->query('hours', 24)));
        $report = $this->reportService->buildReport($hours);

        return view()->file(base_path('modules/Logger/Views/performance.blade.php'), [
            'currentPage' => 'performance',
            'selectedHours' => $hours,
            'report' => $report,
            'queryCache' => QueryCacheService::stats(),
        ]);
    }
}