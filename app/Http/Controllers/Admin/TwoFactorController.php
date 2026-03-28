<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\AdminUser;
use App\Services\AdminSessionService;
use App\Services\CatminEventBus;
use App\Services\RbacPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
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

    public function showVerify(): View|RedirectResponse
    {
        if (!session('catmin_2fa_pending')) {
            return redirect()->route('admin.login');
        }

        return view('admin.pages.2fa.verify');
    }

    public function verify(Request $request, AdminSessionService $sessionService): RedirectResponse
    {
        if (!session('catmin_2fa_pending')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'max:64'],
        ]);

        $user = $this->pendingUser();
        if (!$user || !$user->two_factor_enabled) {
            return redirect()->route('admin.login')->withErrors(['otp' => 'Contexte 2FA invalide.']);
        }

        $code = trim((string) $request->input('otp'));
        $secret = $this->decryptSecret((string) $user->two_factor_secret);

        $validTotp = $secret !== '' && preg_match('/^\d{6}$/', $code) === 1
            ? $this->google2fa->verifyKey($secret, $code)
            : false;

        $validRecovery = false;
        if (!$validTotp) {
            $validRecovery = $this->consumeRecoveryCode($user, $code);
        }

        if (!$validTotp && !$validRecovery) {
            CatminEventBus::dispatch(CatminEventBus::AUTH_2FA_CHALLENGE_FAILED, [
                'guard' => 'admin',
                'username' => (string) session('catmin_2fa_pending_username', ''),
                'ip' => $request->ip(),
            ]);

            $this->logAudit('auth.2fa.failed', 'Code 2FA invalide', ['ip' => $request->ip()], 'warning');

            return back()->withErrors(['otp' => 'Code invalide.']);
        }

        if ($validRecovery) {
            $this->logAudit('auth.2fa.recovery.used', 'Code de recuperation 2FA utilise', ['ip' => $request->ip()]);
        }

        CatminEventBus::dispatch(CatminEventBus::AUTH_2FA_CHALLENGE_PASSED, [
            'guard' => 'admin',
            'username' => (string) session('catmin_2fa_pending_username', ''),
            'ip' => $request->ip(),
        ]);

        return $this->completePendingLogin($request, $sessionService, $user);
    }

    public function showSetup(Request $request): View|RedirectResponse
    {
        $user = $this->currentUserFromSession($request);
        if (!$user) {
            return redirect()->route('admin.login');
        }

        $setupSecret = (string) $request->session()->get('catmin_2fa_setup_secret', '');
        if (!$user->two_factor_enabled && $setupSecret === '') {
            $setupSecret = $this->google2fa->generateSecretKey();
            $request->session()->put('catmin_2fa_setup_secret', $setupSecret);
        }

        return view('admin.pages.2fa.setup', [
            'enabled' => (bool) $user->two_factor_enabled,
            'setupSecret' => $setupSecret,
            'qrCodeUri' => $setupSecret !== ''
                ? $this->google2fa->getQRCodeUrl(config('app.name', 'CATMIN'), (string) $user->username, $setupSecret)
                : null,
            'recoveryCodes' => $request->session()->pull('catmin_2fa_new_recovery_codes', []),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $this->currentUserFromSession($request);
        if (!$user) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $setupSecret = (string) $request->session()->get('catmin_2fa_setup_secret', '');
        if ($setupSecret === '') {
            return redirect()->route('admin.2fa.setup')->withErrors(['otp' => 'Session de configuration expiree.']);
        }

        if (!$this->google2fa->verifyKey($setupSecret, (string) $request->input('otp'))) {
            return back()->withErrors(['otp' => 'Code invalide.']);
        }

        $codes = $this->generateRecoveryCodes();
        $user->two_factor_enabled = true;
        $user->two_factor_secret = Crypt::encryptString($setupSecret);
        $user->two_factor_recovery_codes = $this->hashRecoveryCodes($codes);
        $user->save();

        $request->session()->forget('catmin_2fa_setup_secret');
        $request->session()->put('catmin_2fa_new_recovery_codes', $codes);

        $this->logAudit('auth.2fa.enabled', '2FA activee sur le compte admin', ['admin_user_id' => $user->id]);

        return redirect()->route('admin.2fa.setup')->with('status', '2FA activee avec succes.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $this->currentUserFromSession($request);
        if (!$user) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'max:64'],
        ]);

        if (!$this->validateCurrentSecondFactor($user, (string) $request->input('otp'))) {
            return back()->withErrors(['otp' => 'Code invalide.']);
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        $request->session()->forget('catmin_2fa_setup_secret');
        $request->session()->forget('catmin_2fa_new_recovery_codes');

        $this->logAudit('auth.2fa.disabled', '2FA desactivee sur le compte admin', ['admin_user_id' => $user->id]);

        return redirect()->route('admin.2fa.setup')->with('status', '2FA desactivee.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $this->currentUserFromSession($request);
        if (!$user) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'max:64'],
        ]);

        if (!$this->validateCurrentSecondFactor($user, (string) $request->input('otp'))) {
            return back()->withErrors(['otp' => 'Code invalide.']);
        }

        $codes = $this->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $this->hashRecoveryCodes($codes);
        $user->save();

        $request->session()->put('catmin_2fa_new_recovery_codes', $codes);

        $this->logAudit('auth.2fa.recovery.regenerated', 'Codes de recuperation 2FA regeneres', ['admin_user_id' => $user->id]);

        return redirect()->route('admin.2fa.setup')->with('status', 'Nouveaux codes generes.');
    }

    private function completePendingLogin(Request $request, AdminSessionService $sessionService, AdminUser $user): RedirectResponse
    {
        $request->session()->put('catmin_admin_authenticated', true);
        $request->session()->put('catmin_admin_user_id', (int) $user->id);
        $request->session()->put('catmin_admin_username', (string) $user->username);
        $request->session()->put('catmin_admin_login_at', now()->timestamp);
        $request->session()->put('catmin_admin_last_activity_at', now()->timestamp);
        $request->session()->put('catmin_2fa_verified', true);
        $request->session()->forget('catmin_2fa_pending');
        $request->session()->forget('catmin_2fa_pending_username');
        $request->session()->forget('catmin_2fa_pending_user_id');

        $rbacContext = RbacPermissionService::resolveContextForUsername((string) $user->username);
        $request->session()->put('catmin_rbac_roles', $rbacContext['roles']);
        $request->session()->put('catmin_rbac_permissions', $rbacContext['permissions']);
        $request->session()->put('catmin_rbac_source', $rbacContext['source']);

        $sessionService->registerSession($request, (int) $user->id);

        $this->logAudit('auth.2fa.verified', 'Verification 2FA reussie', ['ip' => $request->ip()], 'info');

        return redirect()->route('admin.index');
    }

    private function pendingUser(): ?AdminUser
    {
        $id = (int) session('catmin_2fa_pending_user_id', 0);

        return $id > 0 ? AdminUser::find($id) : null;
    }

    private function currentUserFromSession(Request $request): ?AdminUser
    {
        $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);

        return $adminUserId > 0 ? AdminUser::find($adminUserId) : null;
    }

    private function decryptSecret(string $encrypted): string
    {
        if ($encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return '';
        }
    }

    private function validateCurrentSecondFactor(AdminUser $user, string $code): bool
    {
        $code = trim($code);
        $secret = $this->decryptSecret((string) $user->two_factor_secret);

        if ($secret !== '' && preg_match('/^\d{6}$/', $code) === 1 && $this->google2fa->verifyKey($secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    private function consumeRecoveryCode(AdminUser $user, string $rawCode): bool
    {
        $normalized = strtoupper(str_replace([' ', '-'], '', trim($rawCode)));
        if ($normalized === '') {
            return false;
        }

        $hash = hash('sha256', $normalized);
        $stored = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : [];

        if (!in_array($hash, $stored, true)) {
            return false;
        }

        $user->two_factor_recovery_codes = array_values(array_filter(
            $stored,
            static fn (string $item): bool => $item !== $hash
        ));
        $user->save();

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    /**
     * @param array<int, string> $codes
     * @return array<int, string>
     */
    private function hashRecoveryCodes(array $codes): array
    {
        return array_map(
            static fn (string $code): string => hash('sha256', strtoupper(str_replace([' ', '-'], '', $code))),
            $codes
        );
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
                (string) session('catmin_2fa_pending_username', session('catmin_admin_username', ''))
            );
        } catch (\Throwable) {
        }
    }
}
