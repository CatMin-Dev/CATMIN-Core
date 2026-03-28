<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Logger\Services\SystemLogService;

class AdminSessionsController extends Controller
{
    public function index(Request $request, AdminSessionService $sessions): View
    {
        $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);

        return view('admin.pages.sessions.index', [
            'currentPage' => 'sessions',
            'sessions' => $adminUserId > 0 ? $sessions->listActiveForAdmin($adminUserId) : [],
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function revoke(Request $request, AdminSessionService $sessions): RedirectResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:128'],
        ]);

        $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);
        $target = (string) $data['session_id'];

        if ($adminUserId <= 0) {
            return redirect()->route('admin.sessions.index')->with('error', 'Session admin invalide.');
        }

        $revoked = $sessions->revokeBySessionId($adminUserId, $target);

        $this->logAudit('admin.session.revoked', 'Session admin revoquee', [
            'target_session' => $target,
            'revoked' => $revoked,
        ]);

        if ($target === $request->session()->getId()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')->with('status', 'Session courante revoquee.');
        }

        return redirect()->route('admin.sessions.index')
            ->with($revoked ? 'status' : 'error', $revoked ? 'Session revoquee.' : 'Aucune session a revoquer.');
    }

    public function revokeOthers(Request $request, AdminSessionService $sessions): RedirectResponse
    {
        $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);
        if ($adminUserId <= 0) {
            return redirect()->route('admin.sessions.index')->with('error', 'Session admin invalide.');
        }

        $count = $sessions->revokeOthers($adminUserId, $request->session()->getId());

        $this->logAudit('admin.session.revoke_others', 'Revocation des autres sessions admin', [
            'revoked_count' => $count,
        ]);

        return redirect()->route('admin.sessions.index')->with('status', $count . ' session(s) revoquee(s).');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logAudit(string $event, string $message, array $context = []): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                $event,
                $message,
                $context,
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
        }
    }
}
