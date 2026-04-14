<?php

declare(strict_types=1);

final class CoreModuleDataRetentionPolicy
{
    public function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        if (in_array($value, ['remove_data', 'drop_data', 'uninstalled_drop_data'], true)) {
            return 'drop_data';
        }
        if (in_array($value, ['archive_data', 'keep_data', 'uninstalled_keep_data'], true)) {
            return 'keep_data';
        }

        return 'keep_data';
    }

    public function isDestructive(string $value): bool
    {
        return $this->normalize($value) === 'drop_data';
    }
}

