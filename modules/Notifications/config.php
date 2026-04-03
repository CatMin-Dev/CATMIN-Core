<?php

return [
    'fields' => [
        [
            'key' => 'notification_retention_days',
            'label' => 'Rétention des notifications (jours)',
            'type' => 'integer',
            'default' => 30,
            'rules' => 'required|integer|min:1|max:365',
            'help' => 'Durée de conservation des notifications avant purge automatique.',
        ],
        [
            'key' => 'notification_email_enabled',
            'label' => 'Email admin sur alerte critique',
            'type' => 'boolean',
            'default' => true,
            'rules' => 'boolean',
            'help' => 'Envoyer un email admin quand le seuil d\'alertes critiques est dépassé.',
        ],
        [
            'key' => 'notification_critical_threshold',
            'label' => 'Seuil alertes critiques (email)',
            'type' => 'integer',
            'default' => 3,
            'rules' => 'required|integer|min:1|max:50',
            'help' => 'Nombre d\'alertes critiques dans l\'heure déclenchant l\'email admin.',
        ],
        [
            'key' => 'notification_dropdown_limit',
            'label' => 'Limite dropdown cloche',
            'type' => 'integer',
            'default' => 8,
            'rules' => 'required|integer|min:3|max:20',
            'help' => 'Nombre de notifications affichées dans le dropdown de la topbar.',
        ],
    ],
];
