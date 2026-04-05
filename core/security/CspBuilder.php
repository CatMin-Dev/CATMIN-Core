<?php

declare(strict_types=1);

namespace Core\security;

final class CspBuilder
{
    public function build(array $directives): string
    {
        $parts = [];

        foreach ($directives as $name => $values) {
            if (!is_array($values) || $values === []) {
                continue;
            }

            $parts[] = trim((string) $name) . ' ' . implode(' ', array_map('strval', $values));
        }

        return implode('; ', $parts);
    }

    public function defaultPolicy(): array
    {
        return [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'img-src' => ["'self'", 'data:'],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'script-src' => ["'self'"],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'form-action' => ["'self'"],
        ];
    }
}
