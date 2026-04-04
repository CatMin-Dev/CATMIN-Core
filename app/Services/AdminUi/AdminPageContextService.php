<?php

namespace App\Services\AdminUi;

use App\Services\AdminNavigation\AdminNavigationRenderService;

class AdminPageContextService
{
    public function __construct(private readonly AdminNavigationRenderService $navigation)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function current(): array
    {
        $route = (string) request()->route()?->getName();
        $tree = (array) ($this->navigation->sidebarViewModel()['tree'] ?? []);

        $breadcrumbs = [['label' => 'Dashboard', 'url' => admin_route('index')]];
        $title = 'Administration';

        foreach ($tree as $master) {
            foreach ((array) ($master['children'] ?? []) as $sub) {
                foreach ((array) ($sub['children'] ?? []) as $leaf) {
                    if (empty($leaf['active'])) {
                        continue;
                    }

                    $breadcrumbs[] = ['label' => (string) ($master['label'] ?? 'Section'), 'url' => null];
                    $breadcrumbs[] = ['label' => (string) ($sub['label'] ?? 'Sous-section'), 'url' => null];
                    $breadcrumbs[] = ['label' => (string) ($leaf['label'] ?? 'Page'), 'url' => (string) ($leaf['url'] ?? '')];
                    $title = (string) ($leaf['label'] ?? 'Administration');
                    break 3;
                }
            }
        }

        return [
            'route' => $route,
            'title' => $title,
            'subtitle' => null,
            'breadcrumbs' => $breadcrumbs,
        ];
    }
}
