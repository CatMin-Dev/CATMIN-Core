<?php

declare(strict_types=1);

final class CoreModuleDataRetentionPolicy
{
    public function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['keep_data', 'remove_data', 'archive_data'], true) ? $value : 'keep_data';
    }
}

