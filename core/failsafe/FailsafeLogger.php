<?php

declare(strict_types=1);

namespace Core\failsafe;

use Core\logs\Logger;

final class FailsafeLogger
{
    public function log(string $severity, string $message, array $context = []): void
    {
        $safeContext = $this->sanitizeContext($context);
        $payload = array_merge([
            'severity' => $severity,
            'route' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ], $safeContext);

        if (in_array($severity, ['critical', 'error'], true)) {
            Logger::error('[failsafe] ' . $message, $payload);
            return;
        }

        Logger::info('[failsafe] ' . $message, $payload);
    }

    private function sanitizeContext(array $context): array
    {
        $blocked = ['password', 'passwd', 'secret', 'token', 'authorization', 'cookie'];
        $safe = [];
        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            foreach ($blocked as $needle) {
                if (str_contains($normalizedKey, $needle)) {
                    $safe[$key] = '[redacted]';
                    continue 2;
                }
            }

            if (is_scalar($value) || $value === null) {
                $safe[$key] = $value;
                continue;
            }

            $safe[$key] = is_array($value) ? '[array]' : '[object]';
        }

        return $safe;
    }
}

