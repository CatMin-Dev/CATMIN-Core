<?php

declare(strict_types=1);

namespace Install;

final class InstallerModulePlanner
{
    public function plan(array $modules): array
    {
        $planned = [];

        foreach ($modules as $name) {
            $planned[] = [
                'name' => (string) $name,
                'status' => 'planned',
            ];
        }

        return $planned;
    }
}
