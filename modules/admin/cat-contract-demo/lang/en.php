<?php

declare(strict_types=1);

return [
    'auth.required' => 'Access denied.',

    'nav.label' => 'Contract Demo',
    'nav.settings_label' => 'Contract Demo',

    'admin.page_title' => 'Contract Demo',
    'admin.page_description' => 'CATMIN sample module - CORE/MODULE contract validation.',
    'admin.breadcrumb_label' => 'Contract Demo',

    'settings.page_title' => 'Settings - Contract demo',
    'settings.page_description' => 'CATMIN sample module settings.',
    'settings.breadcrumb_label' => 'Contract demo',
    'settings.module_link_label' => 'Contract Demo',
    'settings.heading' => 'Settings - Contract demo',
    'settings.intro' => 'Sample module settings panel integrated in Settings navigation. Accessible with {permission} permission.',
    'settings.card.route_title' => 'Settings route',
    'settings.card.route_desc' => 'Route loaded from routes/settings.php in admin zone through the multi-zone loader.',
    'settings.card.permission_title' => 'Permission',
    'settings.card.permission_desc' => 'Restricted access - only roles with {permission} can access this page.',

    'dashboard.heading' => 'Contract Demo - Dashboard',
    'dashboard.intro' => 'This module validates the CATMIN CORE/MODULE integration contract. Routes, views, layout, permissions and sidebar are operational.',
    'dashboard.card.admin_route_title' => 'Admin route',
    'dashboard.card.admin_route_desc' => 'Route loaded by module contract in admin zone with layout and {permission} permission.',
    'dashboard.card.auth_title' => 'Authentication',
    'dashboard.card.auth_badge' => 'Authenticated',
    'dashboard.card.auth_desc' => 'Access protected by the automatic admin authentication middleware.',
    'dashboard.card.permission_title' => 'Permission',
    'dashboard.card.permission_desc' => 'Rights checked with auth_can(). Automatically assigned to super-admin on activation.',

    'permissions.example.read.name' => 'Read contract demo module',
    'permissions.example.read.description' => 'Allows reading contract demo pages and records.',
    'permissions.example.write.name' => 'Edit contract demo module',
    'permissions.example.write.description' => 'Allows creating and updating contract demo data.',
    'permissions.example.delete.name' => 'Delete in contract demo module',
    'permissions.example.delete.description' => 'Allows deleting contract demo records.',
    'permissions.example.settings.name' => 'Manage contract demo settings',
    'permissions.example.settings.description' => 'Allows changing contract demo configuration.',
    'permissions.example.tools.name' => 'Use contract demo tools',
    'permissions.example.tools.description' => 'Allows executing contract demo maintenance tools.',
];
