<?php

declare(strict_types=1);

namespace Modules\CatSlug\services;

final class SlugValidationService
{
    public function validate(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return ['valid' => false, 'reason' => 'Slug vide'];
        }

        if (strlen($slug) > 191) {
            return ['valid' => false, 'reason' => 'Slug trop long'];
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            return ['valid' => false, 'reason' => 'Format slug invalide'];
        }

        return ['valid' => true, 'reason' => 'ok'];
    }
}
