<?php

declare(strict_types=1);

return [
    'auth.required' => 'Acces non autorise.',

    'nav.label' => 'Contract Demo',
    'nav.settings_label' => 'Contract Demo',

    'admin.page_title' => 'Contract Demo',
    'admin.page_description' => 'Module exemple CATMIN - validation du contrat CORE/MODULE.',
    'admin.breadcrumb_label' => 'Contract Demo',

    'settings.page_title' => 'Parametres - Contract demo',
    'settings.page_description' => 'Reglages du module exemple CATMIN.',
    'settings.breadcrumb_label' => 'Contract demo',
    'settings.module_link_label' => 'Contract Demo',
    'settings.heading' => 'Parametres - Contract demo',
    'settings.intro' => 'Panneau de reglages du module exemple, integre dans la navigation Parametres. Accessible avec la permission {permission}.',
    'settings.card.route_title' => 'Route settings',
    'settings.card.route_desc' => 'Route chargee depuis routes/settings.php, zone admin, via le multi-zone loader.',
    'settings.card.permission_title' => 'Permission',
    'settings.card.permission_desc' => 'Acces restreint - seul un role ayant {permission} peut acceder a cette page.',

    'dashboard.heading' => 'Contract Demo - Tableau de bord',
    'dashboard.intro' => 'Ce module valide le contrat d integration CORE/MODULE CATMIN. Routes, vues, layout, permissions et sidebar sont tous operationnels.',
    'dashboard.card.admin_route_title' => 'Route admin',
    'dashboard.card.admin_route_desc' => 'Route chargee via le contrat module, zone admin, avec layout et permission {permission}.',
    'dashboard.card.auth_title' => 'Authentification',
    'dashboard.card.auth_badge' => 'Authentifie',
    'dashboard.card.auth_desc' => 'Acces protege par le middleware d authentification admin automatique.',
    'dashboard.card.permission_title' => 'Permission',
    'dashboard.card.permission_desc' => 'Droits verifies via auth_can(). Assignes automatiquement au role super-admin a l activation.',

    'permissions.example.read.name' => 'Lire le module Contract Demo',
    'permissions.example.read.description' => 'Permet de consulter les pages et enregistrements du module Contract Demo.',
    'permissions.example.write.name' => 'Modifier le module Contract Demo',
    'permissions.example.write.description' => 'Permet de creer et modifier les donnees du module Contract Demo.',
    'permissions.example.delete.name' => 'Supprimer dans le module Contract Demo',
    'permissions.example.delete.description' => 'Permet de supprimer les enregistrements du module Contract Demo.',
    'permissions.example.settings.name' => 'Gerer les reglages du module Contract Demo',
    'permissions.example.settings.description' => 'Permet de modifier la configuration du module Contract Demo.',
    'permissions.example.tools.name' => 'Utiliser les outils du module Contract Demo',
    'permissions.example.tools.description' => 'Permet d executer les outils de maintenance du module Contract Demo.',
];
