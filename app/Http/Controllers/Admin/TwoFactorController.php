<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Logger\Services\SystemLogService;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Affiche la page de vérification 2FA après login réussi.
     */
    public function showVerify(): View|RedirectResponse
    {
        if (!session('catmin_2fa_pending')) {
            return redirect()->route('admin.login');
        }

        return view('admin.pages.2fa.verify');
    }

    /**
     * Vérifie le code OTP soumis.
     */
    public function verify(Request $request): RedirectResponse
    {
        if (!session('catmin_2fa_pending')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $secret = (string) config('catmin.two_factor.secret', '');

        if ($secret === '') {
            // 2FA non configurée — on laisse passer (ne doit pas arriver si activé)
            return $this->completePendingLogin($request);
        }

        $valid = $this->google2fa->verifyKey($secret, (string) $request->input('otp'));

        if (!$valid) {
            try {
                app(SystemLogService::class)->logAudit(
                    'auth.2fa.failed',
                    'Code 2FA invalide',
                    ['ip' => $request->ip()],
                    'warning',
                    (string) session('catmin_2fa_pending_username', '')
                );
            } catch (\Throwable) {}

            return back()->withErrors(['otp' => 'Code invalide. Réessaie.']);
        }

        try {
            app(SystemLogService::class)->logAudit(
                'auth.2fa.verified',
                'Verification 2FA reussie',
                ['ip' => $request->ip()],
                'info',
                (string) session('catmin_2fa_pending_username', '')
            );
        } catch (\Throwable) {}

        return $this->completePendingLogin($request);
    }

    /**
     * Affiche la page de setup 2FA (génère un QR code).
     */
    public function showSetup(): View
    {
        $username = (string) session('catmin_admin_username', 'admin');
        $secret   = (string) config('catmin.two_factor.secret', '');
        $enabled  = (bool) config('catmin.two_factor.enabled', false);

        $qrCodeUri  = null;
        $newSecret  = null;

        if (!$enabled) {
            // Génère un nouveau secret pour l'afficher à l'admin
            $newSecret  = $this->google2fa->generateSecretKey();
            $qrCodeUri  = $this->google2fa->getQRCodeUrl(
                config('app.name', 'CATMIN'),
                $username,
                $newSecret
            );
        }

        return view('admin.pages.2fa.setup', compact('enabled', 'newSecret', 'qrCodeUri', 'secret'));
    }

    /**
     * Finalise le login après 2FA validé (ou si non requise).
     */
    private function completePendingLogin(Request $request): RedirectResponse
    {
        $request->session()->put('catmin_admin_authenticated', true);
        $request->session()->put('catmin_2fa_verified', true);
        $request->session()->forget('catmin_2fa_pending');
        $request->session()->forget('catmin_2fa_pending_username');

        return redirect()->route('admin.index');
    }
}
