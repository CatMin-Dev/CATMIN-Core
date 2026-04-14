<?php

declare(strict_types=1);

namespace Core\front;

final class FrontCoreLoader
{
    private ?array $context = null;

    public function __construct(
        private readonly FrontSnapshotAdapter $snapshot = new FrontSnapshotAdapter(),
        private readonly FrontSecurityPolicy $policy = new FrontSecurityPolicy(),
        private readonly FrontRuntimeEnforcement $enforcement = new FrontRuntimeEnforcement(),
        private readonly FrontContextBuilder $builder = new FrontContextBuilder(),
    ) {}

    public function boot(bool $refresh = false): array
    {
        if ($this->context !== null && !$refresh) {
            return $this->context;
        }

        $modules = $this->snapshot->frontModules();
        $modules = array_values(array_filter($modules, fn (array $module): bool => $this->policy->allowsModule($module)));
        $modules = $this->enforcement->enforce($modules);

        $this->context = $this->builder->build($modules);

        return $this->context;
    }
}
