<?php

declare(strict_types=1);

namespace Modules\CatSlug\services;

use Modules\CatSlug\repositories\SlugRegistryRepository;

final class SlugRegistryService
{
    public function __construct(
        private readonly SlugRegistryRepository $repo,
        private readonly SlugGeneratorService $generator,
        private readonly SlugCollisionService $collision,
        private readonly SlugValidationService $validator,
    ) {
    }

    public function generateAndReserve(string $entityType, int $entityId, string $sourceText, string $scopeKey = 'global', ?string $manualSlug = null): array
    {
        $scopeKey = trim($scopeKey) === '' ? 'global' : trim($scopeKey);
        $entityType = trim($entityType);
        if ($entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => $this->message('entity_type et entity_id sont obligatoires', 'entity_type and entity_id are required')];
        }

        $raw = trim((string) $manualSlug) !== '' ? (string) $manualSlug : $sourceText;
        $base = $this->generator->generate($raw);
        if ($base === '') {
            return ['ok' => false, 'message' => $this->message('Impossible de generer un slug', 'Unable to generate a slug')];
        }

        $valid = $this->validator->validate($base);
        if (!($valid['valid'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($valid['reason'] ?? 'Slug invalide')];
        }

        $exclude = ['entity_type' => $entityType, 'entity_id' => $entityId];
        $unique = $this->collision->ensureUnique(
            $base,
            fn (string $slug): bool => $this->repo->exists($slug, $scopeKey, $exclude)
        );

        $ok = $this->repo->reserve($entityType, $entityId, $unique, $scopeKey, true);
        return [
            'ok' => $ok,
            'slug' => $unique,
            'message' => $ok ? $this->message('Slug reserve', 'Slug reserved') : $this->message('Reservation echouee', 'Reservation failed'),
        ];
    }

    public function validateInScope(string $slug, string $scopeKey = 'global', ?array $excludeEntity = null): array
    {
        $valid = $this->validator->validate($slug);
        if (!($valid['valid'] ?? false)) {
            return ['valid' => false, 'available' => false, 'reason' => (string) ($valid['reason'] ?? 'invalide')];
        }

        $exists = $this->repo->exists($slug, trim($scopeKey) === '' ? 'global' : trim($scopeKey), $excludeEntity);
        return ['valid' => true, 'available' => !$exists, 'reason' => $exists ? $this->message('deja utilise', 'already used') : $this->message('disponible', 'available')];
    }

    public function suggest(string $sourceText, string $scopeKey = 'global'): string
    {
        $base = $this->generator->generate($sourceText);
        if ($base === '') {
            return '';
        }

        return $this->collision->ensureUnique(
            $base,
            fn (string $slug): bool => $this->repo->exists($slug, trim($scopeKey) === '' ? 'global' : trim($scopeKey), null)
        );
    }

    public function recent(int $limit = 50): array
    {
        return $this->repo->recent($limit);
    }

    private function message(string $fr, string $en): string
    {
        $locale = function_exists('catmin_locale') ? strtolower(trim(catmin_locale())) : 'fr';
        return $locale === 'en' ? $en : $fr;
    }
}
