<?php

return [
    'navigation_items' => [
        [
            'label' => 'WYSIWYG',
            'icon' => 'bi bi-pencil-square',
            'route' => 'addon.cat_wysiwyg.index',
            'active_when' => ['addon.cat_wysiwyg.*'],
            'permission' => 'addon.cat_wysiwyg.menu',
        ],
    ],

    'defaults' => [
        'toolbar_tools' => [
            'bold', 'italic', 'underline', 'strike',
            'align-left', 'align-center', 'align-right', 'align-justify',
            'ul', 'ol', 'blockquote', 'code-block',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'text-color', 'bg-color',
            'link', 'clear', 'undo', 'redo',
            'panel',
        ],
        'enabled_fields' => [
            'pages.create.content',
            'pages.edit.content',
            'articles.create.content',
            'articles.edit.content',
        ],
        'snippets' => [
            [
                'label' => 'Bloc CTA',
                'html' => '<div class="p-3 border rounded bg-light"><h3>Votre titre</h3><p>Votre texte.</p><p><a class="btn btn-primary" href="#">Action</a></p></div>'
            ],
            [
                'label' => 'Alerte Info',
                'html' => '<div class="alert alert-info" role="alert"><strong>Info:</strong> Votre message.</div>'
            ]
        ],
    ],
];
