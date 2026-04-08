<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-repository-repository.php';
require_once CATMIN_CORE . '/module-repository-validator.php';
require_once CATMIN_CORE . '/module-repository-checker.php';
require_once CATMIN_CORE . '/module-repository-trust.php';
require_once CATMIN_CORE . '/module-repository-logger.php';

final class CoreModuleRepositoryRegistry
{
    public function __construct(
        private readonly CoreModuleRepositoryRepository $repository = new CoreModuleRepositoryRepository(),
        private readonly CoreModuleRepositoryValidator $validator = new CoreModuleRepositoryValidator(),
        private readonly CoreModuleRepositoryChecker $checker = new CoreModuleRepositoryChecker(),
        private readonly CoreModuleRepositoryTrust $trust = new CoreModuleRepositoryTrust(),
        private readonly CoreModuleRepositoryLogger $logger = new CoreModuleRepositoryLogger(),
    ) {}

    public function addRepository(array $payload): array
    {
        $validated = $this->validator->validate($payload);
        if (!(bool) ($validated['ok'] ?? false)) {
            return ['ok' => false, 'message' => implode(' ', (array) ($validated['errors'] ?? []))];
        }

        $data = (array) ($validated['data'] ?? []);
        $existing = $this->repository->findBySlug((string) ($data['slug'] ?? ''));
        if (is_array($existing)) {
            return ['ok' => false, 'message' => 'Slug déjà utilisé.'];
        }

        $ok = $this->repository->create($data);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Ajout dépôt impossible. ' . $this->repository->lastError()];
        }

        $created = $this->repository->findBySlug((string) ($data['slug'] ?? ''));
        if (is_array($created)) {
            $check = $this->checker->check($created);
            $this->repository->updateCheckStatus((int) ($created['id'] ?? 0), (string) ($check['status'] ?? 'error'), (string) ($check['message'] ?? ''));
        }

        $this->logger->info('Module repository added', ['slug' => (string) ($data['slug'] ?? ''), 'trust' => (string) ($data['trust_level'] ?? '')]);

        return ['ok' => true, 'message' => 'Dépôt ajouté.'];
    }

    public function updateRepository(int $id, array $payload): array
    {
        $current = $this->repository->findById($id);
        if (!is_array($current)) {
            return ['ok' => false, 'message' => 'Dépôt introuvable.'];
        }

        $validated = $this->validator->validate($payload);
        if (!(bool) ($validated['ok'] ?? false)) {
            return ['ok' => false, 'message' => implode(' ', (array) ($validated['errors'] ?? []))];
        }

        $data = (array) ($validated['data'] ?? []);
        $sameSlug = strtolower((string) ($current['slug'] ?? '')) === strtolower((string) ($data['slug'] ?? ''));
        if (!$sameSlug) {
            $existing = $this->repository->findBySlug((string) ($data['slug'] ?? ''));
            if (is_array($existing)) {
                return ['ok' => false, 'message' => 'Slug déjà utilisé.'];
            }
        }

        if (strtolower((string) ($current['trust_level'] ?? '')) !== strtolower((string) ($data['trust_level'] ?? ''))) {
            $this->logger->warning('Repository trust level changed', [
                'id' => $id,
                'slug' => (string) ($current['slug'] ?? ''),
                'from' => (string) ($current['trust_level'] ?? ''),
                'to' => (string) ($data['trust_level'] ?? ''),
            ]);
        }

        $ok = $this->repository->update($id, $data);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Mise à jour dépôt impossible. ' . $this->repository->lastError()];
        }

        return ['ok' => true, 'message' => 'Dépôt mis à jour.'];
    }

    public function disableRepository(int $id): array
    {
        $ok = $this->repository->setEnabled($id, false);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Désactivation dépôt impossible.'];
        }
        return ['ok' => true, 'message' => 'Dépôt désactivé.'];
    }

    public function enableRepository(int $id): array
    {
        $ok = $this->repository->setEnabled($id, true);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Activation dépôt impossible.'];
        }
        return ['ok' => true, 'message' => 'Dépôt activé.'];
    }

    public function removeRepository(int $id): array
    {
        $row = $this->repository->findById($id);
        if (!is_array($row)) {
            return ['ok' => false, 'message' => 'Dépôt introuvable.'];
        }
        if (!empty($row['is_official'])) {
            return ['ok' => false, 'message' => 'Suppression dépôt officiel refusée.'];
        }

        $ok = $this->repository->delete($id);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Suppression dépôt impossible.'];
        }

        return ['ok' => true, 'message' => 'Dépôt supprimé.'];
    }

    public function validateRepository(array $payload): array
    {
        return $this->validator->validate($payload);
    }

    public function checkRepository(int $id): array
    {
        $row = $this->repository->findById($id);
        if (!is_array($row)) {
            return ['ok' => false, 'message' => 'Dépôt introuvable.'];
        }

        $check = $this->checker->check($row);
        $this->repository->updateCheckStatus($id, (string) ($check['status'] ?? 'error'), (string) ($check['message'] ?? ''));

        return [
            'ok' => (bool) ($check['ok'] ?? false),
            'message' => (string) ($check['message'] ?? 'Check terminé.'),
            'details' => $check,
        ];
    }

    public function listRepositories(): array
    {
        return $this->repository->listAll();
    }

    public function getEnabledRepositories(): array
    {
        $rows = $this->repository->listEnabled();
        $policy = $this->policy();

        return array_values(array_filter($rows, static function (array $row) use ($policy): bool {
            $level = strtolower((string) ($row['trust_level'] ?? 'community'));
            return match ($level) {
                'official' => (bool) ($policy['allow_official'] ?? true),
                'trusted' => (bool) ($policy['allow_trusted'] ?? true),
                'community' => (bool) ($policy['allow_community'] ?? false),
                default => false,
            };
        }));
    }

    public function getOfficialRepositories(): array
    {
        return array_values(array_filter($this->repository->listAll(), static fn (array $row): bool => !empty($row['is_official'])));
    }

    public function savePolicy(array $payload): array
    {
        $ok = $this->repository->savePolicy($payload);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Sauvegarde policy impossible. ' . $this->repository->lastError()];
        }
        return ['ok' => true, 'message' => 'Policies market mises à jour.'];
    }

    public function policy(): array
    {
        return $this->repository->loadPolicy();
    }

    public function trustEvaluator(): CoreModuleRepositoryTrust
    {
        return $this->trust;
    }
}
