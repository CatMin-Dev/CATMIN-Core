<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordResetMail;
use App\Services\AdminPasswordResetService;
use App\Services\CatminEventBus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Logger\Services\SystemLogService;

class AdminPasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('admin.pages.auth.forgot-password');
    }

    public function sendResetLink(Request $request, AdminPasswordResetService $service): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim((string) $data['email']));
        $token = $service->requestReset($email, $request->ip());

        if ($token !== null) {
            $resetUrl = route('admin.password.reset', [
                'token' => $token,
                'email' => $email,
            ]);

            $expiresIn = (int) config('catmin.admin.password_reset_expire_minutes', 60);
            Mail::to($email)->send(new AdminPasswordResetMail($resetUrl, $expiresIn));

            CatminEventBus::dispatch(CatminEventBus::AUTH_PASSWORD_RESET_REQUESTED, [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            $this->logAudit('auth.password.reset.requested', 'Demande reset password admin', [
                'email_hash' => substr(hash('sha256', $email), 0, 16),
                'ip' => $request->ip(),
            ]);
        }

        return back()->with('status', 'Si un compte existe, un lien de reinitialisation a ete envoye.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('admin.pages.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request, AdminPasswordResetService $service): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $email = strtolower(trim((string) $data['email']));
        $token = (string) $data['token'];

        if (!$service->isValidToken($email, $token)) {
            $this->logAudit('auth.password.reset.invalid', 'Token reset password invalide', [
                'email_hash' => substr(hash('sha256', $email), 0, 16),
                'ip' => $request->ip(),
            ], 'warning');

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Le lien de reinitialisation est invalide ou expire.']);
        }

        $ok = $service->resetPassword($email, $token, (string) $data['password'], $request->ip());

        if (!$ok) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'La reinitialisation a echoue.']);
        }

        CatminEventBus::dispatch(CatminEventBus::AUTH_PASSWORD_RESET_COMPLETED, [
            'email' => $email,
            'ip' => $request->ip(),
        ]);

        $this->logAudit('auth.password.reset.completed', 'Reset password admin reussi', [
            'email_hash' => substr(hash('sha256', $email), 0, 16),
            'ip' => $request->ip(),
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Mot de passe reinitialise. Vous pouvez vous connecter.');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logAudit(string $event, string $message, array $context = [], string $level = 'info'): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                $event,
                $message,
                $context,
                $level,
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
            // Never break flow because logging failed.
        }
    }
}
