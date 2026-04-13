<?php

declare(strict_types=1);

namespace Modules\CatSlug\services;

final class SlugValidationService
{
    public function validate(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return ['valid' => false, 'reason' => $this->message('Slug vide', 'Empty slug')];
        }

        if (strlen($slug) > 191) {
            return ['valid' => false, 'reason' => $this->message('Slug trop long', 'Slug is too long')];
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            return ['valid' => false, 'reason' => $this->message('Format slug invalide', 'Invalid slug format')];
        }

        return ['valid' => true, 'reason' => 'ok'];
    }

    private function message(string $fr, string $en): string
    {
        $locale = function_exists('catmin_locale') ? strtolower(trim(catmin_locale())) : 'fr';
        return $locale === 'en' ? $en : $fr;
    }
}
