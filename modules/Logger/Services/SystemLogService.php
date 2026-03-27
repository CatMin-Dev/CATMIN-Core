<?php

namespace Modules\Logger\Services;

use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Modules\Logger\Models\SystemLog;
use Throwable;

class SystemLogService
{
    /**
     * @param array<string, mixed> $context
     */
    public function logAudit(string $event, string $message, array $context = [], string $level = 'info', ?string $adminUsername = null): void
    {
        if (!$this->canWrite()) {
            return;
        }

        $this->write([
            'channel' => 'audit',
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context' => $this->sanitizeContext($context),
            'admin_username' => $adminUsername ?? '',
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function logError(Throwable $throwable, array $context = []): void
    {
        if (!$this->canWrite()) {
            return;
        }

        $this->write([
            'channel' => 'application',
            'level' => 'error',
            'event' => 'exception.reported',
            'message' => $throwable->getMessage(),
            'context' => array_merge($context, [
                'exception' => get_class($throwable),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'code' => $throwable->getCode(),
            ]),
            'status_code' => 500,
        ]);
    }

    public function logAdminAction(Request $request, int $statusCode): void
    {
        if (!$this->canWrite()) {
            return;
        }

        if (!$request->session()->get('catmin_admin_authenticated', false)) {
            return;
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return;
        }

        $routeName = (string) optional($request->route())->getName();

        if ($routeName === 'admin.logger.index') {
            return;
        }

        $payload = [
            'channel' => 'admin',
            'level' => $statusCode >= 400 ? 'warning' : 'info',
            'event' => 'admin.action',
            'message' => sprintf(
                '%s %s (%d)',
                strtoupper((string) $request->method()),
                $routeName !== '' ? $routeName : (string) $request->path(),
                $statusCode
            ),
            'context' => [
                'route' => $routeName,
                'input_keys' => array_values(array_keys($request->except(['password', 'password_confirmation']))),
            ],
            'admin_username' => (string) $request->session()->get('catmin_admin_username', ''),
            'method' => strtoupper((string) $request->method()),
            'url' => (string) $request->fullUrl(),
            'ip_address' => (string) $request->ip(),
            'status_code' => $statusCode,
        ];

        $this->write($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function write(array $payload): void
    {
        try {
            SystemLog::query()->create([
                'channel' => (string) ($payload['channel'] ?? 'application'),
                'level' => (string) ($payload['level'] ?? 'info'),
                'event' => (string) ($payload['event'] ?? 'event'),
                'message' => (string) ($payload['message'] ?? ''),
                'context' => (array) ($payload['context'] ?? []),
                'admin_username' => (string) ($payload['admin_username'] ?? ''),
                'method' => (string) ($payload['method'] ?? ''),
                'url' => (string) ($payload['url'] ?? ''),
                'ip_address' => (string) ($payload['ip_address'] ?? ''),
                'status_code' => (int) ($payload['status_code'] ?? 0),
            ]);
        } catch (Throwable) {
            // Never break app flow because the logger failed.
        }
    }

    private function canWrite(): bool
    {
        if (!ModuleManager::exists('logger')) {
            return false;
        }

        return ModuleManager::isEnabled('logger');
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'secret',
            'authorization',
        ];

        foreach ($context as $key => $value) {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, $sensitiveKeys, true)) {
                $context[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
            }
        }

        return $context;
    }
}
