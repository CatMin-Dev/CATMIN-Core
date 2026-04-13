<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

final class AuthorValidationService
{
    public function __construct(private readonly AuthorRepository $repo) {}

    /** Generate a URL-safe slug from a display name */
    public function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, [
            'é' => 'e','è' => 'e','ê' => 'e','ë' => 'e',
            'à' => 'a','â' => 'a','ä' => 'a',
            'î' => 'i','ï' => 'i',
            'ô' => 'o','ö' => 'o',
            'ù' => 'u','û' => 'u','ü' => 'u',
            'ç' => 'c','ñ' => 'n',
        ]);
        $text = (string) preg_replace('/[^a-z0-9\-]+/', '-', $text);
        $text = (string) preg_replace('/-{2,}/', '-', $text);
        return trim($text, '-');
    }

    /** Build a unique slug, appending numeric suffix if needed */
    public function uniqueSlug(string $base, int $excludeId = 0): string
    {
        $slug = $this->slugify($base);
        if ($slug === '') {
            $slug = 'author';
        }
        $candidate = $slug;
        $i = 2;
        while ($this->repo->slugExists($candidate, $excludeId)) {
            $candidate = $slug . '-' . $i;
            $i++;
        }
        return $candidate;
    }

    /** Validate a profile creation/update payload, returns array of error strings */
    public function validate(array $data, int $excludeId = 0): array
    {
        $errors = [];

        $displayName = trim((string) ($data['display_name'] ?? ''));
        if ($displayName === '') {
            $errors[] = 'Le nom d\'affichage est requis.';
        } elseif (mb_strlen($displayName, 'UTF-8') > 160) {
            $errors[] = 'Le nom d\'affichage ne doit pas dépasser 160 caractères.';
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $errors[] = 'Le slug est requis.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets.';
        } elseif ($this->repo->slugExists($slug, $excludeId)) {
            $errors[] = 'Ce slug est déjà utilisé par un autre profil.';
        }

        $visibility = trim((string) ($data['visibility'] ?? 'public'));
        if (!in_array($visibility, ['public', 'private', 'unlisted'], true)) {
            $errors[] = 'Visibilité invalide (public, private, unlisted).';
        }

        $websiteUrl = trim((string) ($data['website_url'] ?? ''));
        if ($websiteUrl !== '' && !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'L\'URL du site web est invalide.';
        }

        return $errors;
    }
}
