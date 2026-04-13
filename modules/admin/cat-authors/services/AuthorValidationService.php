<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

final class AuthorValidationService
{
    public function __construct(private readonly AuthorRepository $repo) {}

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

    public function validate(array $data, int $excludeId = 0): array
    {
        $errors = [];

        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            $errors[] = $this->message('Le compte admin est requis.', 'The admin account is required.');
        } elseif (!$this->repo->adminUserExists($userId)) {
            $errors[] = $this->message('Le compte admin selectionne est introuvable.', 'The selected admin account could not be found.');
        } else {
            $existingProfile = $this->repo->findProfileByUserId($userId);
            if ($existingProfile !== null && (int) ($existingProfile['id'] ?? 0) !== $excludeId) {
                $errors[] = $this->message('Ce compte admin possede deja une fiche auteur.', 'This admin account already has an author record.');
            }
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        if ($firstName === '') {
            $errors[] = $this->message('Le prenom est requis.', 'First name is required.');
        } elseif (mb_strlen($firstName, 'UTF-8') > 120) {
            $errors[] = $this->message('Le prenom ne doit pas depasser 120 caracteres.', 'First name must not exceed 120 characters.');
        }

        $lastName = trim((string) ($data['last_name'] ?? ''));
        if ($lastName === '') {
            $errors[] = $this->message('Le nom est requis.', 'Last name is required.');
        } elseif (mb_strlen($lastName, 'UTF-8') > 120) {
            $errors[] = $this->message('Le nom ne doit pas depasser 120 caracteres.', 'Last name must not exceed 120 characters.');
        }

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

        $socialsRaw = $data['socials_json'] ?? null;
        if (is_string($socialsRaw) && $socialsRaw !== '') {
            $socials = json_decode($socialsRaw, true);
            if (!is_array($socials)) {
                $errors[] = $this->message('Le format des reseaux sociaux est invalide.', 'The social network format is invalid.');
            } else {
                foreach ($socials as $social) {
                    if (!is_array($social)) {
                        $errors[] = $this->message('Le format des reseaux sociaux est invalide.', 'The social network format is invalid.');
                        break;
                    }
                    $network = strtolower(trim((string) ($social['network'] ?? '')));
                    $url = trim((string) ($social['url'] ?? ''));
                    if ($network === '' || $url === '') {
                        $errors[] = $this->message('Chaque reseau social doit avoir un type et une URL.', 'Each social network entry must have a type and a URL.');
                        break;
                    }
                    if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        $errors[] = $this->message('Chaque lien social doit etre une URL valide.', 'Each social link must be a valid URL.');
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    private function message(string $fr, string $en): string
    {
        $locale = function_exists('catmin_locale') ? strtolower(trim(catmin_locale())) : 'fr';
        return $locale === 'en' ? $en : $fr;
    }
}
