<?php

declare(strict_types=1);

?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-1">Contract Demo — Tableau de bord</h2>
                <p class="text-body-secondary mb-0">
                    Ce module valide le contrat d'intégration CORE/MODULE CATMIN.
                    Routes, vues, layout, permissions et sidebar sont tous opérationnels.
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2"><i class="bi bi-check-circle-fill text-success me-1"></i> Route admin</h3>
                <code class="small">GET /contract-demo</code>
                <p class="text-body-secondary small mt-2 mb-0">
                    Route chargée via le contrat module, zone admin, avec layout et permission <code>example.read</code>.
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2"><i class="bi bi-shield-check text-primary me-1"></i> Authentification</h3>
                <span class="badge text-bg-success">Authentifié</span>
                <p class="text-body-secondary small mt-2 mb-0">
                    Accès protégé par le middleware d'authentification admin automatique.
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2"><i class="bi bi-person-lock text-warning me-1"></i> Permission</h3>
                <code class="small">example.read</code>
                <p class="text-body-secondary small mt-2 mb-0">
                    Droits vérifiés via <code>auth_can()</code>. Assignés automatiquement au rôle super-admin à l'activation.
                </p>
            </div>
        </div>
    </div>
</div>
