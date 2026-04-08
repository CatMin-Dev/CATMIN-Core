<?php

declare(strict_types=1);

namespace Core\failsafe;

require_once CATMIN_CORE . '/error-dispatcher.php';

final class FailsafeManager
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        $logger = new FailsafeLogger();
        $classifier = new IncidentClassifier();

        set_exception_handler(static function (\Throwable $throwable) use ($logger, $classifier): void {
            $severity = $classifier->classifyFromThrowable($throwable);
            $logger->log($severity, 'Unhandled exception', [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'message' => substr($throwable->getMessage(), 0, 200),
            ]);

            (new \CoreErrorDispatcher())->outputForFatal(500, [
                'title' => 'Erreur interne',
                'message' => 'Une erreur est survenue. L\'incident a ete journalise.',
                'incident_severity' => $severity,
            ]);
        });

        set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($logger): bool {
            $isFatal = in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
            $logger->log($isFatal ? 'critical' : 'error', 'Runtime error', [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
                'message' => substr($message, 0, 200),
            ]);

            return false;
        });

        register_shutdown_function(static function () use ($logger): void {
            $lastError = error_get_last();
            if (!is_array($lastError)) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array((int) ($lastError['type'] ?? 0), $fatalTypes, true)) {
                return;
            }

            if (headers_sent()) {
                return;
            }

            $logger->log('critical', 'Shutdown fatal error', [
                'type' => (int) ($lastError['type'] ?? 0),
                'file' => (string) ($lastError['file'] ?? ''),
                'line' => (int) ($lastError['line'] ?? 0),
                'message' => substr((string) ($lastError['message'] ?? ''), 0, 200),
            ]);

            (new \CoreErrorDispatcher())->outputForFatal(500, [
                'title' => 'Service indisponible',
                'message' => 'Le service rencontre une erreur temporaire.',
                'incident_severity' => 'critical',
            ]);
        });

        self::$registered = true;
    }
}

