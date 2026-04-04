<?php

namespace App\Services\AdminUi;

use App\Services\AdminNavigation\AdminNavigationRenderService;

class AdminTopbarService
{
    public function __construct(
        private readonly AdminPageContextService $contextService,
        private readonly AdminNavigationRenderService $navigation,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function build(): array
    {
        $context = $this->contextService->current();
        $route = (string) ($context['route'] ?? '');

        $contextual = collect(AdminActionRegistry::forPage($route))
            ->filter(fn (array $action) => empty($action['permission']) || catmin_can((string) $action['permission']))
            ->values()
            ->all();

        $global = collect(AdminActionRegistry::global())
            ->filter(fn (array $action) => empty($action['permission']) || catmin_can((string) $action['permission']))
            ->values()
            ->all();

        return [
            'context' => $context,
            'context_actions' => $contextual,
            'global_actions' => $global,
            'command_items' => (array) ($this->navigation->sidebarViewModel()['commands'] ?? []),
            'system_badges' => [
                ['label' => app()->environment(), 'tone' => 'secondary'],
                ['label' => config('catmin.admin.path', 'admin'), 'tone' => 'light'],
            ],
        ];
    }
}
