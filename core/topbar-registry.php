<?php

declare(strict_types=1);

final class CoreTopbarRegistry
{
    /**
     * @return array<string, bool>
     */
    public function capabilities(): array
    {
        return [
            'search' => true,
            'language' => true,
            'notifications' => true,
            'apps' => true,
            'settings' => true,
            'theme' => true,
            'fullscreen' => true,
            'profile' => true,
        ];
    }
}
