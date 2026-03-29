<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;

final class SecurityHardeningService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function collectGuardrails(): array
    {
        if (!(bool) config('catmin.security.guardrails.enabled', true)) {
            return [];
        }

        return [
            $this->checkAppDebugInProduction(),
            $this->checkAdminDefaultCredentials(),
            $this->checkInternalApiToken(),
            $this->checkWebhookIncomingSecret(),
            $this->checkSessionCookieSecurity(),
            $this->checkTwoFactorCoverage(),
            $this->checkSecurityHeadersConfig(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @return array<string, mixed>
     */
    public function summarize(array $checks): array
    {
        $critical = 0;
        $warning = 0;

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'ok');
            if ($status === 'critical') {
                $critical++;
            }
            if ($status === 'warning') {
                $warning++;
            }
        }

        return [
            'ok' => $critical === 0,
            'status' => $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'ok'),
            'critical' => $critical,
            'warning' => $warning,
            'total' => count($checks),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function installCheck(): array
    {
        $checks = $this->collectGuardrails();
        $summary = $this->summarize($checks);

        $criticalMessages = collect($checks)
            ->where('status', 'critical')
            ->pluck('message')
            ->filter()
            ->values()
            ->all();

        $warningMessages = collect($checks)
            ->where('status', 'warning')
            ->pluck('message')
            ->filter()
            ->values()
            ->all();

        $message = 'Guardrails securite OK.';
        if ($summary['critical'] > 0) {
            $message = sprintf('%d garde-fou(x) critique(s) detecte(s).', $summary['critical']);
        } elseif ($summary['warning'] > 0) {
            $message = sprintf('%d avertissement(s) securite detecte(s).', $summary['warning']);
        }

        return [
            'ok' => (bool) $summary['ok'],
            'status' => (string) $summary['status'],
            'critical_count' => (int) $summary['critical'],
            'warning_count' => (int) $summary['warning'],
            'checks' => $checks,
            'critical' => $criticalMessages,
            'warnings' => $warningMessages,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkAppDebugInProduction(): array
    {
        $isProduction = $this->isProduction();
        $debugEnabled = (bool) config('app.debug', false);

        if ($isProduction && $debugEnabled) {
            return $this->checkRow('app_debug_prod', 'critical', 'APP_DEBUG actif en production.');
        }

        return $this->checkRow('app_debug_prod', 'ok', 'Mode debug conforme pour l environnement courant.');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkAdminDefaultCredentials(): array
    {
        $password = (string) config('catmin.admin.password', '');
        $username = strtolower((string) config('catmin.admin.username', 'admin'));
        $blocked = array_map(
            static fn (mixed $value): string => strtolower((string) $value),
            (array) config('catmin.security.guardrails.critical_admin_passwords', [])
        );
        $isWeak = $password === '' || in_array(strtolower($password), $blocked, true) || strlen($password) < 12;
        $isProd = $this->isProduction();

        if ($isWeak && $isProd) {
            return $this->checkRow('admin_default_password', 'critical', 'Mot de passe admin trop faible en production.');
        }

        if ($isWeak) {
            return $this->checkRow('admin_default_password', 'warning', 'Mot de passe admin faible detecte (a renforcer avant prod).');
        }

        if ($username === 'admin') {
            return $this->checkRow('admin_default_password', 'warning', 'Username admin par defaut detecte (recommandation de personnalisation).');
        }

        return $this->checkRow('admin_default_password', 'ok', 'Credentials admin hors profils faibles connus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkInternalApiToken(): array
    {
        $token = (string) config('catmin.api.internal_token', '');
        $isProd = $this->isProduction();

        if (!$this->isStrongSecret($token)) {
            return $this->checkRow(
                'internal_api_token',
                $isProd ? 'critical' : 'warning',
                'Token API interne absent ou trop faible.'
            );
        }

        return $this->checkRow('internal_api_token', 'ok', 'Token API interne present et robuste.');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWebhookIncomingSecret(): array
    {
        $secret = (string) config('catmin.webhooks.incoming_secret', '');
        $isProd = $this->isProduction();

        if ($secret === '') {
            return $this->checkRow(
                'webhook_incoming_secret',
                $isProd ? 'warning' : 'ok',
                $isProd
                    ? 'Webhook incoming secret non defini (signature HMAC inactive).'
                    : 'Webhook incoming secret optionnel hors production.'
            );
        }

        if (!$this->isStrongSecret($secret)) {
            return $this->checkRow('webhook_incoming_secret', 'warning', 'Webhook incoming secret faible (rotation/reinforcement requis).');
        }

        return $this->checkRow('webhook_incoming_secret', 'ok', 'Webhook incoming secret present et robuste.');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSessionCookieSecurity(): array
    {
        $isProd = $this->isProduction();
        $secure = (bool) config('session.secure', false);
        $sameSite = strtolower((string) config('session.same_site', 'lax'));

        if ($isProd && !$secure) {
            return $this->checkRow('session_cookie_security', 'critical', 'SESSION_SECURE_COOKIE doit etre actif en production.');
        }

        if (!in_array($sameSite, ['lax', 'strict'], true)) {
            return $this->checkRow('session_cookie_security', 'warning', 'SESSION_SAME_SITE devrait rester sur lax ou strict.');
        }

        return $this->checkRow('session_cookie_security', 'ok', 'Configuration cookie session conforme.');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkTwoFactorCoverage(): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('admin_users')) {
                return $this->checkRow('two_factor_coverage', 'warning', 'Table admin_users absente: couverture 2FA non verifiable.');
            }

            $activeAdmins = AdminUser::query()->where('is_active', true)->count();
            if ($activeAdmins === 0) {
                return $this->checkRow('two_factor_coverage', 'warning', 'Aucun admin actif detecte pour verifier la 2FA.');
            }

            $withTwoFactor = AdminUser::query()
                ->where('is_active', true)
                ->where('two_factor_enabled', true)
                ->count();

            if ($withTwoFactor === 0) {
                return $this->checkRow('two_factor_coverage', 'warning', '2FA desactivee pour tous les comptes admin actifs.');
            }
        } catch (\Throwable) {
            return $this->checkRow('two_factor_coverage', 'warning', 'Impossible de verifier la couverture 2FA.');
        }

        return $this->checkRow('two_factor_coverage', 'ok', 'Au moins un compte admin actif est protege par 2FA.');
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSecurityHeadersConfig(): array
    {
        $enabled = (bool) config('catmin.security.headers.enabled', true);
        $csp = trim((string) config('catmin.security.headers.csp', ''));

        if (!$enabled) {
            return $this->checkRow('security_headers', 'warning', 'Security headers middleware desactive.');
        }

        if ($csp === '') {
            return $this->checkRow('security_headers', 'warning', 'CSP vide: politique de contenu non appliquee.');
        }

        return $this->checkRow('security_headers', 'ok', 'Security headers actifs avec CSP configuree.');
    }

    private function isStrongSecret(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        $minLength = max(12, (int) config('catmin.security.guardrails.min_secret_length', 20));
        if (strlen($trimmed) < $minLength) {
            return false;
        }

        $hasUpper = preg_match('/[A-Z]/', $trimmed) === 1;
        $hasLower = preg_match('/[a-z]/', $trimmed) === 1;
        $hasDigit = preg_match('/\d/', $trimmed) === 1;
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $trimmed) === 1;

        return ($hasUpper && $hasLower && $hasDigit) || ($hasLower && $hasDigit && $hasSymbol);
    }

    private function isProduction(): bool
    {
        $appEnv = strtolower((string) config('app.env', ''));

        return $appEnv === 'production' || app()->environment('production');
    }

    /**
     * @return array<string, string>
     */
    private function checkRow(string $key, string $status, string $message): array
    {
        return [
            'key' => $key,
            'status' => $status,
            'message' => $message,
        ];
    }
}
