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
            $errors[] = $this->message('Le nom d\'affichage est requis.', 'Display name is required.');
        } elseif (mb_strlen($displayName, 'UTF-8') > 160) {
            $errors[] = $this->message('Le nom d\'affichage ne doit pas dépasser 160 caractères.', 'Display name must not exceed 160 characters.');
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $errors[] = $this->message('Le slug est requis.', 'Slug is required.');
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = $this->message('Le slug ne peut contenir que des lettres minuscules, chiffres et tirets.', 'Slug may only contain lowercase letters, numbers, and hyphens.');
        } elseif ($this->repo->slugExists($slug, $excludeId)) {
            $errors[] = $this->message('Ce slug est déjà utilisé par un autre profil.', 'This slug is already used by another profile.');
        }

        $visibility = trim((string) ($data['visibility'] ?? 'public'));
        if (!in_array($visibility, ['public', 'private', 'unlisted'], true)) {
            $errors[] = $this->message('Visibilité invalide (public, private, unlisted).', 'Invalid visibility (public, private, unlisted).');
        }

        $websiteUrl = trim((string) ($data['website_url'] ?? ''));
        if ($websiteUrl !== '' && !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            $errors[] = $this->message('L\'URL du site web est invalide.', 'Website URL is invalid.');
        }

        return $errors;
    }

    private function message(string $fr, string $en): string
    {
        $locale = function_exists('catmin_locale') ? strtolower(trim(catmin_locale())) : 'fr';
        return $locale === 'en' ? $en : $fr;
    }
}
