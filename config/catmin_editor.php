<?php

return [
    'enabled' => (bool) env('CATMIN_EDITOR_ENABLED', true),

    // Scope = module.form identifier, field = DB/input name.
    'fields' => [
        'pages.create' => [
            'content' => true,
        ],
        'pages.edit' => [
            'content' => true,
        ],
        'articles.create' => [
            'content' => true,
        ],
        'articles.edit' => [
            'content' => true,
        ],
    ],

    'snippets' => [
        // Alerts
        [
            'label' => 'Alerte Info',
            'html' => '<div class="alert alert-info" role="alert"><strong>Information:</strong> Ajoutez votre message.</div>',
        ],
        [
            'label' => 'Alerte Success',
            'html' => '<div class="alert alert-success" role="alert"><strong>Succes:</strong> Operation validee.</div>',
        ],
        [
            'label' => 'Alerte Warning',
            'html' => '<div class="alert alert-warning" role="alert"><strong>Attention:</strong> Verifiez vos donnees.</div>',
        ],
        [
            'label' => 'Alerte Danger',
            'html' => '<div class="alert alert-danger" role="alert"><strong>Erreur:</strong> Action impossible.</div>',
        ],
        // Cards
        [
            'label' => 'Card Simple',
            'html' => '<div class="card"><div class="card-body">Contenu</div></div>',
        ],
        [
            'label' => 'Card Shadow',
            'html' => '<div class="card shadow"><div class="card-body"><h5 class="card-title">Titre</h5><p class="card-text">Description courte.</p></div></div>',
        ],
        // CTA & Hero
        [
            'label' => 'Bloc CTA',
            'html' => '<div class="p-3 border rounded bg-light"><h3>Votre titre</h3><p>Votre texte de presentation.</p><p><a class="btn btn-primary" href="#">Bouton action</a></p></div>',
        ],
        [
            'label' => 'Hero Intro',
            'html' => '<section class="p-4 rounded bg-light"><h2 class="mb-3">Titre hero</h2><p class="lead mb-3">Introduction courte et impactante.</p><a class="btn btn-primary" href="#">Appel a action</a></section>',
        ],
        // Columns/Layouts
        [
            'label' => '2 colonnes',
            'html' => '<div class="row g-3"><div class="col-md-6"><p>Colonne A</p></div><div class="col-md-6"><p>Colonne B</p></div></div>',
        ],
        [
            'label' => '3 colonnes',
            'html' => '<div class="row g-3"><div class="col-md-4"><p>Colonne A</p></div><div class="col-md-4"><p>Colonne B</p></div><div class="col-md-4"><p>Colonne C</p></div></div>',
        ],
        [
            'label' => '4 colonnes',
            'html' => '<div class="row g-3"><div class="col-md-3"><p>A</p></div><div class="col-md-3"><p>B</p></div><div class="col-md-3"><p>C</p></div><div class="col-md-3"><p>D</p></div></div>',
        ],
        // Boxes with styling
        [
            'label' => 'Box ombre douce',
            'html' => '<div class="p-4 rounded shadow"><h5>Titre</h5><p>Contenu avec ombre douce.</p></div>',
        ],
        [
            'label' => 'Box bordure epaisse',
            'html' => '<div class="p-4 rounded" style="border: 4px solid #374151;"><h5>Titre</h5><p>Bordure personnalisee.</p></div>',
        ],
        [
            'label' => 'Box accent bleu',
            'html' => '<div class="p-4 rounded" style="border-left: 5px solid #0d6efd;"><h5>Titre</h5><p>Accent bleu a gauche.</p></div>',
        ],
        // Other
        [
            'label' => 'Citation',
            'html' => '<blockquote class="blockquote"><p>Votre citation ici.</p><footer class="blockquote-footer">Auteur</footer></blockquote>',
        ],
        [
            'label' => 'Code block',
            'html' => '<pre class="bg-dark text-light p-3 rounded"><code>// Votre code...</code></pre>',
        ],
        [
            'label' => 'Table Tarif',
            'html' => '<table class="table table-bordered"><thead><tr><th>Offre</th><th>Prix</th></tr></thead><tbody><tr><td>Starter</td><td>9 EUR</td></tr><tr><td>Pro</td><td>29 EUR</td></tr></tbody></table>',
        ],
    ],

    'paragraphs' => [
        [
            'label' => 'Paragraphe',
            'html' => '<p>Texte</p>',
        ],
        [
            'label' => 'Titre H2',
            'html' => '<h2>Titre</h2>',
        ],
        [
            'label' => 'Display Heading',
            'html' => '<h1 class="display-5">Titre display</h1>',
        ],
        [
            'label' => 'Lead',
            'html' => '<p class="lead">Texte introductif.</p>',
        ],
        [
            'label' => 'Petit texte',
            'html' => '<small class="text-muted">Texte secondaire.</small>',
        ],
        [
            'label' => 'Citation Bootstrap',
            'html' => '<blockquote class="blockquote"><p>Texte</p><footer class="blockquote-footer">Source</footer></blockquote>',
        ],
    ],

    'blocks' => [
        [
            'label' => 'Liste UL',
            'html' => '<ul><li>Element 1</li><li>Element 2</li><li>Element 3</li></ul>',
        ],
        [
            'label' => 'Liste OL',
            'html' => '<ol><li>Premier</li><li>Deuxieme</li><li>Troisieme</li></ol>',
        ],
        [
            'label' => 'Liste Inline',
            'html' => '<ul class="list-inline"><li class="list-inline-item">Tag 1</li><li class="list-inline-item">Tag 2</li><li class="list-inline-item">Tag 3</li></ul>',
        ],
        [
            'label' => 'Table Base',
            'html' => '<table class="table"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table>',
        ],
        [
            'label' => 'Table Striped',
            'html' => '<table class="table table-striped"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table>',
        ],
        [
            'label' => 'Table Bordered',
            'html' => '<table class="table table-bordered"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table>',
        ],
        [
            'label' => 'Table Responsive',
            'html' => '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table></div>',
        ],
    ],

    'allowed_iframe_hosts' => [],

    'allowed_css_classes' => [
        'alert', 'alert-info', 'alert-warning', 'alert-danger', 'alert-success',
        'btn', 'btn-primary', 'btn-secondary', 'btn-outline-primary',
        'card', 'card-body', 'card-title', 'card-text',
        'row', 'col', 'g-3',
        'col-md-3', 'col-md-4', 'col-md-6', 'col-lg-3', 'col-lg-4', 'col-lg-6',
        'p-3', 'p-4', 'mb-3', 'mb-4', 'rounded', 'border', 'border-3', 'bg-light', 'text-muted',
        'shadow', 'shadow-sm', 'shadow-lg',
        'blockquote', 'blockquote-footer', 'lead',
        'table', 'table-striped', 'table-bordered', 'table-hover', 'table-dark',
        'table-borderless', 'table-sm', 'table-responsive',
        'list-inline', 'list-inline-item',
        'display-5', 'display-6',
        'text-start', 'text-center', 'text-end',
        'bg-dark', 'text-light',
    ],
];
