<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

final class AuthorProfileService
{
    private const SOCIAL_NETWORKS = [
        'twitter',
        'linkedin',
        'github',
        'instagram',
        'mastodon',
        'facebook',
        'youtube',
        'tiktok',
        'telegram',
        'threads',
        'bluesky',
    ];

    public function __construct(
        private readonly AuthorRepository $repo,
        private readonly AuthorValidationService $validator
    ) {}

    public function dashboard(): array
    {
        return [
            'total'           => $this->repo->countProfiles(),
            'profiles'        => $this->repo->allProfiles(),
            'users'           => $this->repo->allAdminUsersWithProfileFlag(),
            'available_users' => $this->repo->availableAdminUsers(),
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
        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        $displayName = trim((string) ($input['display_name'] ?? ''));
        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        $socialNetworks = $input['social_network'] ?? [];
        $socialUrls = $input['social_url'] ?? [];
        if (!is_array($socialNetworks)) {
            $socialNetworks = [];
        }
        if (!is_array($socialUrls)) {
            $socialUrls = [];
        }

        $socials = [];
        foreach ($socialNetworks as $index => $networkValue) {
            $network = strtolower(trim((string) $networkValue));
            $url = trim((string) ($socialUrls[$index] ?? ''));
            if ($network === '' || $url === '') {
                continue;
            }
            if (!in_array($network, self::SOCIAL_NETWORKS, true)) {
                continue;
            }
            $socials[] = [
                'network' => $network,
                'url' => $url,
            ];
        }

        $userId = (int) ($input['user_id'] ?? 0);

        return [
            'user_id'         => $userId > 0 ? $userId : null,
            'first_name'      => $firstName,
            'last_name'       => $lastName,
            'display_name'    => $displayName,
            'slug'            => trim((string) ($input['slug'] ?? '')),
            'bio'             => trim((string) ($input['bio'] ?? '')) ?: null,
            'avatar_media_id' => ((int) ($input['avatar_media_id'] ?? 0)) ?: null,
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
