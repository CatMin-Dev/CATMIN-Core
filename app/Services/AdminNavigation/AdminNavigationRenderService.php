<?php

namespace App\Services\AdminNavigation;

class AdminNavigationRenderService
{
    public function __construct(
        private readonly AdminNavigationBuilder $builder,
        private readonly AdminNavigationTreeResolver $resolver,
        private readonly AdminNavigationStateService $stateService,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function sidebarViewModel(): array
    {
        $rawItems = $this->builder->buildRawItems();
        $tree = $this->resolver->resolve($rawItems);
        $tree = $this->stateService->apply($tree);

        $commands = collect($rawItems)->map(function (array $item): array {
            return [
                'label' => (string) $item['label'],
                'url' => (string) $item['url'],
                'category' => (string) $item['section'],
            ];
        })->values()->all();

        return [
            'tree' => $tree,
            'commands' => $commands,
            'collapsed' => (bool) request()->cookie('catmin_nav_collapsed', false),
            'v2_enabled' => (bool) config('catmin.features.admin_navigation_v2', true),
        ];
    }
}
