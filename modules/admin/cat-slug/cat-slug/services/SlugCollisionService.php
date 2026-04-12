<?php

declare(strict_types=1);

namespace Modules\CatSlug\services;

final class SlugCollisionService
{
    public function ensureUnique(string $candidate, callable $exists, int $maxTries = 250): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return '';
        }

        if (!$exists($candidate)) {
            return $candidate;
        }

        for ($i = 2; $i <= $maxTries; $i++) {
            $variant = $candidate . '-' . $i;
            if (!$exists($variant)) {
                return $variant;
            }
        }

        return $candidate . '-' . time();
    }
}
