<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

/**
 * AuthorRoleRegistryService — registre manuel des rôles "auteur-capables".
 *
 * Aucune automation : l'admin sélectionne manuellement les rôles existants
 * qui sont habilités à agir en tant qu'auteurs. Ce registre sert de référence
 * pour les modules consommateurs. Il ne crée, modifie ni n'assigne aucun droit.
 */
final class AuthorRoleRegistryService
{
    public function __construct(private readonly AuthorRepository $repo) {}

    /**
     * Returns all admin roles with a boolean flag `is_author_role` and optional `note`.
     * The list is sourced live from admin_roles, joined with the registry table.
     */
    public function allRolesWithFlag(): array
    {
        return $this->repo->allRolesWithFlag();
    }

    /** IDs of roles currently marked as author-capable */
    public function registeredRoleIds(): array
    {
        return $this->repo->registeredRoleIds();
    }

    /**
     * Persist the full role registry from a submitted form.
     * $roleIds: array of int role IDs to mark as author-capable.
     * $notes:   associative array [role_id => note string].
     */
    public function saveRegistry(array $roleIds, array $notes): void
    {
        $roleIds = array_values(array_filter(array_map('intval', $roleIds)));
        $this->repo->syncRegisteredRoles($roleIds, $notes);
    }
}
