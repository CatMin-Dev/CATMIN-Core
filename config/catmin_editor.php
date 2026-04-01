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
            'snippets' => [
                // ====== ALERTES ======
                ['label' => '🔵 Alerte Info', 'html' => '<div class="alert alert-info" role="alert"><strong>Information:</strong> Ajoutez votre message.</div>'],
                ['label' => '✅ Alerte Success', 'html' => '<div class="alert alert-success" role="alert"><strong>Succes:</strong> Operation validee.</div>'],
                ['label' => '⚠️ Alerte Warning', 'html' => '<div class="alert alert-warning" role="alert"><strong>Attention:</strong> Verifiez vos donnees.</div>'],
                ['label' => '❌ Alerte Danger', 'html' => '<div class="alert alert-danger" role="alert"><strong>Erreur:</strong> Action impossible.</div>'],

                // ====== CARTES ======
                ['label' => '📇 Card Simple', 'html' => '<div class="card"><div class="card-body"><h5 class="card-title">Titre carte</h5><p class="card-text">Contenu courte.</p></div></div>'],
                ['label' => '📇 Card Ombre', 'html' => '<div class="card shadow"><div class="card-body"><h5 class="card-title">Titre</h5><p class="card-text">Description courte.</p><a href="#" class="btn btn-sm btn-primary">Lire plus</a></div></div>'],
                ['label' => '📇 Card Image (2)', 'html' => '<div class="row g-2"><div class="col-md-6"><div class="card"><img src="#" class="card-img-top" alt="Image"><div class="card-body"><h6 class="card-title">Titre 1</h6></div></div></div><div class="col-md-6"><div class="card"><img src="#" class="card-img-top" alt="Image"><div class="card-body"><h6 class="card-title">Titre 2</h6></div></div></div></div>'],
                ['label' => '📇 Card Image (3)', 'html' => '<div class="row g-2"><div class="col-md-4"><div class="card"><img src="#" class="card-img-top" alt="Image"><div class="card-body"><h6 class="card-title">Titre 1</h6></div></div></div><div class="col-md-4"><div class="card"><img src="#" class="card-img-top" alt="Image"><div class="card-body"><h6 class="card-title">Titre 2</h6></div></div></div><div class="col-md-4"><div class="card"><img src="#" class="card-img-top" alt="Image"><div class="card-body"><h6 class="card-title">Titre 3</h6></div></div></div></div>'],

                // ====== BADGES & TAGS ======
                ['label' => '🏷️ Badges Group', 'html' => '<div class="d-flex gap-2 flex-wrap"><span class="badge bg-primary">Primary</span><span class="badge bg-secondary">Secondary</span><span class="badge bg-success">Success</span><span class="badge bg-warning">Warning</span><span class="badge bg-danger">Danger</span><span class="badge bg-info">Info</span></div>'],
                ['label' => '🏷️ Badge List', 'html' => '<ul class="list-unstyled"><li class="mb-2"><span class="badge bg-primary">Feature</span> Description courte</li><li class="mb-2"><span class="badge bg-success">Fixed</span> Probleme resolu</li><li class="mb-2"><span class="badge bg-warning">In progress</span> En cours de travail</li></ul>'],

                // ====== LISTES ======
                ['label' => '📋 List Group Simple', 'html' => '<ul class="list-group"><li class="list-group-item">Element 1</li><li class="list-group-item">Element 2</li><li class="list-group-item">Element 3</li></ul>'],
                ['label' => '📋 List Group Active', 'html' => '<ul class="list-group"><li class="list-group-item active">Element actif</li><li class="list-group-item">Element 2</li><li class="list-group-item">Element 3</li></ul>'],
                ['label' => '📋 List Group Checked', 'html' => '<ul class="list-group"><li class="list-group-item"><input class="form-check-input me-2" type="checkbox" checked> Element 1</li><li class="list-group-item"><input class="form-check-input me-2" type="checkbox"> Element 2</li><li class="list-group-item"><input class="form-check-input me-2" type="checkbox"> Element 3</li></ul>'],

                // ====== COLLAPSE / ACCORDEON ======
                ['label' => '▶️ Collapse (Simple)', 'html' => '<div class="accordion"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">Section 1</button></h2><div id="collapse1" class="accordion-collapse collapse"><div class="accordion-body">Contenu de la section 1.</div></div></div></div>'],
                ['label' => '▶️ Collapse (3 sections)', 'html' => '<div class="accordion"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c1">Section 1</button></h2><div id="c1" class="accordion-collapse collapse"><div class="accordion-body">Contenu 1</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">Section 2</button></h2><div id="c2" class="accordion-collapse collapse"><div class="accordion-body">Contenu 2</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">Section 3</button></h2><div id="c3" class="accordion-collapse collapse"><div class="accordion-body">Contenu 3</div></div></div></div>'],

                // ====== LAYOUTS ======
                ['label' => '📐 2 colonnes', 'html' => '<div class="row g-3"><div class="col-md-6"><p>Colonne A</p></div><div class="col-md-6"><p>Colonne B</p></div></div>'],
                ['label' => '📐 3 colonnes', 'html' => '<div class="row g-3"><div class="col-md-4"><p>Colonne A</p></div><div class="col-md-4"><p>Colonne B</p></div><div class="col-md-4"><p>Colonne C</p></div></div>'],
                ['label' => '📐 4 colonnes', 'html' => '<div class="row g-3"><div class="col-md-3"><p>A</p></div><div class="col-md-3"><p>B</p></div><div class="col-md-3"><p>C</p></div><div class="col-md-3"><p>D</p></div></div>'],

                // ====== BOXES & BLOCS ======
                ['label' => '🎨 Box ombre douce', 'html' => '<div class="p-4 rounded shadow"><h5>Titre</h5><p>Contenu avec ombre douce.</p></div>'],
                ['label' => '🎨 Box bordure epaisse', 'html' => '<div class="p-4 rounded" style="border: 4px solid #374151;"><h5>Titre</h5><p>Bordure personnalisee.</p></div>'],
                ['label' => '🎨 Box accent bleu', 'html' => '<div class="p-4 rounded" style="border-left: 5px solid #0d6efd;"><h5>Titre</h5><p>Accent bleu a gauche.</p></div>'],
                ['label' => '🎨 Box gradient fond', 'html' => '<div class="p-4 rounded text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"><h5>Titre</h5><p>Box avec degradé de couleur.</p></div>'],

                // ====== CTA & HERO ======
                ['label' => '🚀 Bloc CTA', 'html' => '<div class="p-3 border rounded bg-light"><h3>Votre titre</h3><p>Votre texte de presentation.</p><p><a class="btn btn-primary" href="#">Bouton action</a></p></div>'],
                ['label' => '🚀 Hero Intro', 'html' => '<section class="p-4 rounded bg-light"><h2 class="mb-3">Titre hero</h2><p class="lead mb-3">Introduction courte et impactante.</p><a class="btn btn-primary" href="#">Appel a action</a></section>'],

                // ====== CONTENU ======
                ['label' => '💬 Citation', 'html' => '<blockquote class="blockquote"><p>Votre citation ici.</p><footer class="blockquote-footer">Auteur</footer></blockquote>'],
                ['label' => '💻 Code block', 'html' => '<pre class="bg-dark text-light p-3 rounded"><code>&lt;div&gt;Votre code...&lt;/div&gt;</code></pre>'],
                ['label' => '📊 Table Simple', 'html' => '<table class="table"><thead><tr><th>#</th><th>Colonne 1</th><th>Colonne 2</th></tr></thead><tbody><tr><td>1</td><td>Donne 1</td><td>Donne 2</td></tr><tr><td>2</td><td>Donne 3</td><td>Donne 4</td></tr></tbody></table>'],
                ['label' => '📊 Table Tarif', 'html' => '<table class="table table-bordered"><thead><tr><th>Offre</th><th>Prix/mois</th><th>Features</th></tr></thead><tbody><tr><td>Starter</td><td>9 EUR</td><td>Feature 1</td></tr><tr><td>Pro</td><td>29 EUR</td><td>Feature 1 + 2</td></tr></tbody></table>'],
            ],
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
