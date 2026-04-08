<?php

declare(strict_types=1);

final class CoreI18nFallback
{
    public function masterLocale(): string
    {
        return 'fr';
    }

    public function normalize(string $locale): string
    {
        $locale = strtolower(trim($locale));
        return in_array($locale, ['fr', 'en'], true) ? $locale : $this->masterLocale();
    }
}

