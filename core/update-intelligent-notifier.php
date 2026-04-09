<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/notifications-dispatcher.php';
require_once CATMIN_CORE . '/events-bus.php';

final class CoreUpdateIntelligentNotifier
{
    public function notify(array $snapshot, string $adminBase = '/admin'): void
    {
        $dispatcher = new CoreNotificationsDispatcher();
        $core = is_array($snapshot['core'] ?? null) ? $snapshot['core'] : [];
        $modules = is_array($snapshot['modules'] ?? null) ? $snapshot['modules'] : [];
        $coreSeverity = (string) ($snapshot['core_severity'] ?? 'info');

        if ((bool) ($core['update_available'] ?? false)) {
            $coreType = $this->severityToType($coreSeverity);
            $remote = (string) ($core['remote_version'] ?? '-');
            $dispatcher->push([
                'title' => 'Update core disponible',
                'message' => 'Version ' . $remote . ' (' . $coreSeverity . ').',
                'type' => $coreType,
                'source' => 'update-core',
                'action_url' => rtrim($adminBase, '/') . '/system/updates',
            ]);
            catmin_event_emit('update.core.available', [
                'remote_version' => $remote,
                'severity' => $coreSeverity,
            ]);
        }

        foreach ($modules as $row) {
            if (!is_array($row) || !((bool) ($row['has_update'] ?? false))) {
                continue;
            }
            $severity = (string) ($row['update_severity'] ?? 'recommended');
            $type = $this->severityToType($severity);
            $slug = (string) ($row['slug'] ?? 'module');
            $remote = (string) ($row['remote_version'] ?? '-');

            $dispatcher->push([
                'title' => 'Update module: ' . $slug,
                'message' => 'Version ' . $remote . ' (' . $severity . ').',
                'type' => $type,
                'source' => 'update-module',
                'action_url' => rtrim($adminBase, '/') . '/modules/market',
            ]);

            catmin_event_emit('update.module.available', [
                'slug' => $slug,
                'remote_version' => $remote,
                'severity' => $severity,
            ]);
        }
    }

    private function severityToType(string $severity): string
    {
        return match (strtolower(trim($severity))) {
            'critical' => 'critical',
            'security' => 'security',
            'important' => 'important',
            'recommended' => 'recommended',
            default => 'info',
        };
    }
}

