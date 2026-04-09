<?php

declare(strict_types=1);

final class CoreTrustScopeResolver
{
    /** @var array<int,string> */
    private array $allowedScopes = ['official', 'trusted', 'community', 'local_only', 'revoked'];

    public function normalize(string $scope): string
    {
        $scope = strtolower(trim($scope));
        if (!in_array($scope, $this->allowedScopes, true)) {
            return 'community';
        }

        return $scope;
    }

    public function rank(string $scope): int
    {
        return match ($this->normalize($scope)) {
            'official' => 500,
            'trusted' => 400,
            'community' => 300,
            'local_only' => 200,
            'revoked' => 0,
            default => 100,
        };
    }

    public function isEditable(string $scope): bool
    {
        return $this->normalize($scope) === 'local_only';
    }

    public function isUsable(string $scope): bool
    {
        return $this->normalize($scope) !== 'revoked';
    }

    public function resolve(array $entry): string
    {
        if (!empty($entry['revoked'])) {
            return 'revoked';
        }

        $scope = $this->normalize((string) ($entry['scope'] ?? 'community'));
        if (!empty($entry['is_official'])) {
            return 'official';
        }
        if (!empty($entry['is_trusted'])) {
            return 'trusted';
        }

        return $scope;
    }
}
