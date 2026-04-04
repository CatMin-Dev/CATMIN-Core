<?php

namespace App\Services\AdminNavigation;

class AdminNavigationTreeResolver
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function resolve(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $master = $this->masterCategoryFor($item);
            $sub = $this->subCategoryFor($item);

            $masterId = $this->slug($master);
            $subId = $this->slug($master . '-' . $sub);

            if (!isset($groups[$masterId])) {
                $groups[$masterId] = [
                    'id' => $masterId,
                    'label' => $master,
                    'icon' => $this->masterIcon($master),
                    'order' => $this->masterOrder($master),
                    'active' => false,
                    'opened' => false,
                    'children' => [],
                ];
            }

            if (!isset($groups[$masterId]['children'][$subId])) {
                $groups[$masterId]['children'][$subId] = [
                    'id' => $subId,
                    'label' => $sub,
                    'order' => 100,
                    'active' => false,
                    'opened' => false,
                    'children' => [],
                ];
            }

            $groups[$masterId]['children'][$subId]['children'][] = array_merge($item, [
                'children' => [],
                'active' => false,
            ]);
        }

        return collect($groups)
            ->sortBy('order')
            ->map(function (array $master): array {
                $master['children'] = collect($master['children'])
                    ->sortBy('order')
                    ->map(function (array $sub): array {
                        $sub['children'] = collect($sub['children'])
                            ->sortBy('label')
                            ->values()
                            ->all();

                        return $sub;
                    })
                    ->values()
                    ->all();

                return $master;
            })
            ->values()
            ->all();
    }

    /** @param array<string,mixed> $item */
    private function masterCategoryFor(array $item): string
    {
        $section = mb_strtolower((string) ($item['section'] ?? ''), 'UTF-8');
        $label = mb_strtolower((string) ($item['label'] ?? ''), 'UTF-8');

        if (str_contains($label, 'dashboard') || str_contains($label, 'tableau')) return 'Dashboard';
        if (str_contains($label, 'user') || str_contains($label, 'role') || str_contains($label, 'profil') || str_contains($label, 'session') || str_contains($label, '2fa')) return 'Utilisateurs';
        if (str_contains($label, 'page') || str_contains($label, 'article') || str_contains($label, 'media') || str_contains($label, 'menu') || str_contains($label, 'block') || str_contains($label, 'slider')) return 'Contenu';
        if (str_contains($label, 'queue') || str_contains($label, 'cron') || str_contains($label, 'log') || str_contains($label, 'monitor') || str_contains($label, 'cache') || str_contains($label, 'performance') || str_contains($label, 'analytics')) return 'Exploitation';
        if (str_contains($label, 'setting') || str_contains($label, 'param')) return 'Configuration';
        if (str_contains($label, 'marketplace') || str_contains($label, 'webhook') || str_contains($label, 'mailer') || str_contains($label, 'api') || str_contains($label, 'bundle')) return 'Integrations';
        if (str_contains($label, 'crm') || str_contains($label, 'booking') || str_contains($label, 'event') || str_contains($label, 'forms') || str_contains($label, 'shop')) return 'Business / Addons';

        return match ($section) {
            'cms' => 'Contenu',
            'intégrations', 'integrations' => 'Integrations',
            'administration' => 'Configuration',
            default => 'Business / Addons',
        };
    }

    /** @param array<string,mixed> $item */
    private function subCategoryFor(array $item): string
    {
        $master = $this->masterCategoryFor($item);
        $label = (string) ($item['label'] ?? 'General');

        return match ($master) {
            'Dashboard' => 'Overview',
            'Contenu' => 'CMS',
            'Utilisateurs' => 'Acces',
            'Exploitation' => 'Operations',
            'Integrations' => 'Connecteurs',
            'Configuration' => 'Systeme',
            default => $this->startsWithUpper($label),
        };
    }

    private function masterIcon(string $master): string
    {
        return match ($master) {
            'Dashboard' => 'bi bi-speedometer2',
            'Contenu' => 'bi bi-journals',
            'Utilisateurs' => 'bi bi-people',
            'Exploitation' => 'bi bi-activity',
            'Integrations' => 'bi bi-plug',
            'Configuration' => 'bi bi-sliders2',
            default => 'bi bi-grid-1x2',
        };
    }

    private function masterOrder(string $master): int
    {
        return match ($master) {
            'Dashboard' => 10,
            'Contenu' => 20,
            'Utilisateurs' => 30,
            'Exploitation' => 40,
            'Integrations' => 50,
            'Configuration' => 60,
            default => 70,
        };
    }

    private function slug(string $value): string
    {
        $v = strtolower(trim($value));
        $v = preg_replace('/[^a-z0-9]+/', '-', $v) ?? 'item';
        return trim($v, '-');
    }

    private function startsWithUpper(string $value): string
    {
        $value = trim($value);
        return $value === '' ? 'General' : ucfirst($value);
    }
}
