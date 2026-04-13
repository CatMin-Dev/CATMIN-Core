<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

final class AuthorProfileService
{
    public function __construct(
        private readonly AuthorRepository $repo,
        private readonly AuthorValidationService $validator
    ) {}

    public function dashboard(): array
    {
        return [
            'total'    => $this->repo->countProfiles(),
            'profiles' => $this->repo->allProfiles(),
            'users'    => $this->repo->allAdminUsersWithProfileFlag(),
        ];
    }

    public function allProfiles(): array
    {
        return $this->repo->allProfiles();
    }

    public function findProfile(int $id): ?array
    {
        return $this->repo->findProfile($id);
    }

    /** Create profile. Returns ['ok', profile_id] or ['error', message] */
    public function create(array $input): array
    {
        $data = $this->sanitize($input);
        if ($data['slug'] === '') {
            $data['slug'] = $this->validator->uniqueSlug($data['display_name']);
        }
        $errors = $this->validator->validate($data);
        if ($errors !== []) {
            return ['error', implode(' ', $errors)];
        }
        $id = $this->repo->insertProfile($data);
        return ['ok', $id];
    }

    /** Update profile. Returns ['ok', profile_id] or ['error', message] */
    public function update(int $id, array $input): array
    {
        if ($this->repo->findProfile($id) === null) {
            return ['error', $this->message('Profil introuvable.', 'Profile not found.')];
        }
        $data = $this->sanitize($input);
        if ($data['slug'] === '') {
            $data['slug'] = $this->validator->uniqueSlug($data['display_name'], $id);
        }
        $errors = $this->validator->validate($data, $id);
        if ($errors !== []) {
            return ['error', implode(' ', $errors)];
        }
        $this->repo->updateProfile($id, $data);
        return ['ok', $id];
    }

    public function delete(int $id): void
    {
        $this->repo->deleteProfile($id);
    }

    private function sanitize(array $input): array
    {
        $socials = [];
        foreach (['twitter', 'linkedin', 'github', 'instagram', 'mastodon'] as $key) {
            $val = trim((string) ($input['social_' . $key] ?? ''));
            if ($val !== '') {
                $socials[$key] = $val;
            }
        }

        $userId = (int) ($input['user_id'] ?? 0);

        return [
            'user_id'         => $userId > 0 ? $userId : null,
            'display_name'    => trim((string) ($input['display_name'] ?? '')),
            'slug'            => trim((string) ($input['slug'] ?? '')),
            'bio'             => trim((string) ($input['bio'] ?? '')) ?: null,
            'avatar_media_id' => ((int) ($input['avatar_media_id'] ?? 0)) ?: null,
            'website_url'     => trim((string) ($input['website_url'] ?? '')) ?: null,
            'socials_json'    => $socials !== [] ? json_encode($socials, JSON_UNESCAPED_UNICODE) : null,
            'visibility'      => trim((string) ($input['visibility'] ?? 'public')),
        ];
    }

    private function message(string $fr, string $en): string
    {
        $locale = function_exists('catmin_locale') ? strtolower(trim(catmin_locale())) : 'fr';
        return $locale === 'en' ? $en : $fr;
    }
}
