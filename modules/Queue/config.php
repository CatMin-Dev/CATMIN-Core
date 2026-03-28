<?php

return [
    'fields' => [
        [
            'key' => 'failed_jobs_limit',
            'label' => 'Nombre de jobs en echec affiches',
            'type' => 'integer',
            'default' => 20,
            'rules' => 'required|integer|min:5|max:200',
            'help' => 'Limite d affichage dans le panneau admin Queue.',
        ],
        [
            'key' => 'prune_failed_hours',
            'label' => 'Retention des jobs echoues (heures)',
            'type' => 'integer',
            'default' => 72,
            'rules' => 'required|integer|min:1|max:720',
            'help' => 'Utilise par la tache cron queue.prune.',
        ],
    ],
];
