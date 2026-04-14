<?php

declare(strict_types=1);

namespace Core\front;

require_once CATMIN_CORE . '/module-runtime-snapshot.php';

final class FrontSnapshotAdapter
{
    /** @return array<int, array<string,mixed>> */
    public function frontModules(): array
    {
        $runtime = new \CoreModuleRuntimeSnapshot();
        $modules = $runtime->modules();
        $filtered = [];

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }
            if (!((bool) ($module['valid'] ?? false)) || !((bool) ($module['compatible'] ?? false)) || !((bool) ($module['enabled'] ?? false))) {
                continue;
            }

            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            if (!$this->isFrontCapable($manifest)) {
                continue;
            }

            $filtered[] = $module;
        }

        return $filtered;
    }

    private function isFrontCapable(array $manifest): bool
    {
        $zones = is_array($manifest['zones'] ?? null) ? $manifest['zones'] : [];
        foreach ($zones as $zone) {
            $zone = strtolower(trim((string) $zone));
            if ($zone === 'front' || $zone === 'core') {
                return true;
            }
        }

        $load = is_array($manifest['load'] ?? null) ? $manifest['load'] : [];
        foreach (['front_routes', 'front_views', 'front_assets', 'front_bridge'] as $key) {
            if (array_key_exists($key, $load) && (bool) $load[$key] === true) {
                return true;
            }
        }

        return false;
    }
}
