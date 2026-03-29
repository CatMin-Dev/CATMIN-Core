<?php

declare(strict_types=1);

namespace Modules\Logger\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EnvironmentValidatorService;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SystemCheckController extends Controller
{
    public function __construct(
        private readonly EnvironmentValidatorService $validator,
        private readonly MonitoringService $monitoring,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $diagnostic = $this->validator->run();
        $monitoring = $this->monitoring->buildDashboardReport(8);
        $healthScore = (array) ($monitoring['health_score'] ?? []);

        $payload = [
            'diagnostic' => $diagnostic,
            'health_score' => $healthScore,
            'monitoring_status' => (string) (($monitoring['global']['status'] ?? 'ok')),
        ];

        if ($request->query('format') === 'json' || $request->expectsJson()) {
            return response()->json($payload);
        }

        return view()->file(base_path('modules/Logger/Views/system-check.blade.php'), [
            'currentPage' => 'system-check',
            ...$payload,
        ]);
    }

    public function recheck(): RedirectResponse
    {
        $this->monitoring->captureSnapshot();

        return redirect()->route('admin.system.check')->with('status', 'System check relance avec succes.');
    }
}
