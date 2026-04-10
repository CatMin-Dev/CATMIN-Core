<?php

declare(strict_types=1);

final class CoreModuleCapabilityPolicy
{
    /** @return array{ok:bool,warnings:array<int,string>,errors:array<int,string>,capabilities:array<int,string>} */
    public function evaluate(array $manifest, ?string $repoTrust = null): array
    {
        $capabilities = $this->normalizeCapabilities($manifest['capabilities'] ?? []);
        $errors = [];
        $warnings = [];

        $criticalCapabilities = [
            'backups.run',
            'admin.users.read',
            'admin.users.write',
            'db.schema.migrate',
            'external.http',
            'telemetry.emit',
        ];
        $reservedForOfficial = [
            'db.schema.migrate',
            'admin.users.write',
            'backups.run',
        ];
        foreach ($capabilities as $capability) {
            // Accept modular capabilities like "cache.view", "content.pages.write", etc.
            if (preg_match('/^[a-z0-9_]+(?:\.[a-z0-9_]+)+$/', $capability) !== 1) {
                $errors[] = 'Capacité invalide: ' . $capability;
                continue;
            }
            if (in_array($capability, $criticalCapabilities, true)) {
                $warnings[] = 'Capacité critique: ' . $capability;
            }
            if (in_array($capability, $reservedForOfficial, true)) {
                $trust = strtolower(trim((string) $repoTrust));
                if ($trust !== '' && !in_array($trust, ['official', 'trusted'], true)) {
                    $errors[] = 'Capacité réservée pour dépôt non fiable: ' . $capability;
                }
            }
        }

        $criticalCount = count(array_filter(
            $capabilities,
            static fn (string $c): bool => in_array($c, $criticalCapabilities, true)
        ));
        $riskLevel = $criticalCount >= 2 ? 'critical' : ($criticalCount === 1 ? 'high' : (count($capabilities) >= 8 ? 'medium' : 'low'));

        return [
            'ok' => $errors === [],
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
            'capabilities' => $capabilities,
            'critical_count' => $criticalCount,
            'risk_level' => $riskLevel,
        ];
    }

    /** @return array<int,string> */
    private function normalizeCapabilities(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $rows = [];
        foreach ($raw as $value) {
            $capability = strtolower(trim((string) $value));
            if ($capability !== '') {
                $rows[] = $capability;
            }
        }
        return array_values(array_unique($rows));
    }

    /** @return array<int,string> */
    public function allowedCapabilities(): array
    {
        // Kept for compatibility/documentation; validation now relies on pattern checks.
        return [];
    }
}
