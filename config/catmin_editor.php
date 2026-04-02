<?php

return [
    'enabled' => (bool) env('CATMIN_EDITOR_ENABLED', true),

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
        ['label' => '[Alert] Info', 'icon' => 'bi-info-circle', 'html' => '<div class="alert alert-info" role="alert"><strong>Information:</strong> Ajoutez votre message.</div>'],
        ['label' => '[Alert] Success', 'icon' => 'bi-check-circle', 'html' => '<div class="alert alert-success" role="alert"><strong>Succes:</strong> Operation validee.</div>'],
        ['label' => '[Alert] Warning', 'icon' => 'bi-exclamation-triangle', 'html' => '<div class="alert alert-warning" role="alert"><strong>Attention:</strong> Verifiez vos donnees.</div>'],
        ['label' => '[Alert] Danger', 'icon' => 'bi-x-circle', 'html' => '<div class="alert alert-danger" role="alert"><strong>Erreur:</strong> Action impossible.</div>'],

        ['label' => '[Card] Simple', 'icon' => 'bi-card-text', 'html' => '<div class="card"><div class="card-body"><h5 class="card-title">Titre</h5><p class="card-text">Description courte.</p></div></div>'],
        ['label' => '[Card] Shadow', 'icon' => 'bi-card-heading', 'html' => '<div class="card shadow"><div class="card-body"><h5 class="card-title">Titre</h5><p class="card-text">Description courte.</p></div></div>'],
        ['label' => '[Card] Grid 3', 'icon' => 'bi-grid-3x3-gap', 'html' => '<div class="row g-3"><div class="col-md-4"><div class="card"><div class="card-body">Card 1</div></div></div><div class="col-md-4"><div class="card"><div class="card-body">Card 2</div></div></div><div class="col-md-4"><div class="card"><div class="card-body">Card 3</div></div></div></div>'],

        ['label' => '[Badge] Group', 'icon' => 'bi-tags', 'html' => '<div class="d-flex gap-2 flex-wrap"><span class="badge bg-primary">Primary</span><span class="badge bg-secondary">Secondary</span><span class="badge bg-success">Success</span><span class="badge bg-warning">Warning</span><span class="badge bg-danger">Danger</span></div>'],

        ['label' => '[List] Group', 'icon' => 'bi-list-ul', 'html' => '<ul class="list-group"><li class="list-group-item">Element 1</li><li class="list-group-item">Element 2</li><li class="list-group-item">Element 3</li></ul>'],
        ['label' => '[List] Group Active', 'icon' => 'bi-list-check', 'html' => '<ul class="list-group"><li class="list-group-item active">Element actif</li><li class="list-group-item">Element 2</li><li class="list-group-item">Element 3</li></ul>'],

        ['label' => '[Accordion] 1 Section', 'icon' => 'bi-chevron-expand', 'html' => '<div class="accordion" id="accOne"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accOneItem">Section 1</button></h2><div id="accOneItem" class="accordion-collapse collapse show" data-bs-parent="#accOne"><div class="accordion-body">Contenu de la section.</div></div></div></div>'],
        ['label' => '[Accordion] 3 Sections', 'icon' => 'bi-layout-text-sidebar', 'html' => '<div class="accordion" id="accThree"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accA">Section 1</button></h2><div id="accA" class="accordion-collapse collapse show" data-bs-parent="#accThree"><div class="accordion-body">Contenu 1</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accB">Section 2</button></h2><div id="accB" class="accordion-collapse collapse" data-bs-parent="#accThree"><div class="accordion-body">Contenu 2</div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accC">Section 3</button></h2><div id="accC" class="accordion-collapse collapse" data-bs-parent="#accThree"><div class="accordion-body">Contenu 3</div></div></div></div>'],

        ['label' => '[Layout] 2 Colonnes', 'icon' => 'bi-layout-split', 'html' => '<div class="row g-3"><div class="col-md-6"><p>Colonne A</p></div><div class="col-md-6"><p>Colonne B</p></div></div>'],
        ['label' => '[Layout] 3 Colonnes', 'icon' => 'bi-grid-3x2', 'html' => '<div class="row g-3"><div class="col-md-4"><p>Colonne A</p></div><div class="col-md-4"><p>Colonne B</p></div><div class="col-md-4"><p>Colonne C</p></div></div>'],
        ['label' => '[Layout] 4 Colonnes', 'icon' => 'bi-grid-3x3', 'html' => '<div class="row g-3"><div class="col-md-3"><p>A</p></div><div class="col-md-3"><p>B</p></div><div class="col-md-3"><p>C</p></div><div class="col-md-3"><p>D</p></div></div>'],

        ['label' => '[Box] Shadow', 'icon' => 'bi-square', 'html' => '<div class="p-4 rounded shadow"><h5>Titre</h5><p>Contenu avec ombre douce.</p></div>'],
        ['label' => '[Box] Border 4px', 'icon' => 'bi-bounding-box', 'html' => '<div class="p-4 rounded" style="border: 4px solid #374151;"><h5>Titre</h5><p>Bordure personnalisee.</p></div>'],
        ['label' => '[Box] Accent Left', 'icon' => 'bi-text-indent-left', 'html' => '<div class="p-4 rounded" style="border-left: 5px solid #0d6efd;"><h5>Titre</h5><p>Accent bleu a gauche.</p></div>'],

        ['label' => '[Hero] Intro', 'icon' => 'bi-stars', 'html' => '<section class="p-4 rounded bg-light"><h2 class="mb-3">Titre hero</h2><p class="lead mb-3">Introduction courte et impactante.</p><a class="btn btn-primary" href="#">Appel a action</a></section>'],
        ['label' => '[CTA] Bloc Action', 'icon' => 'bi-cursor', 'html' => '<div class="p-3 border rounded bg-light"><h3>Votre titre</h3><p>Votre texte de presentation.</p><p><a class="btn btn-primary" href="#">Bouton action</a></p></div>'],

        ['label' => '[Content] Citation', 'icon' => 'bi-quote', 'html' => '<blockquote class="blockquote"><p>Votre citation ici.</p><footer class="blockquote-footer">Auteur</footer></blockquote>'],
        ['label' => '[Content] Table Tarif', 'icon' => 'bi-table', 'html' => '<table class="table table-bordered"><thead><tr><th>Offre</th><th>Prix/mois</th></tr></thead><tbody><tr><td>Starter</td><td>9 EUR</td></tr><tr><td>Pro</td><td>29 EUR</td></tr></tbody></table>'],
    ],

    'paragraphs' => [
        ['label' => 'Paragraphe', 'html' => '<p>Texte</p>'],
        ['label' => 'Titre H2', 'html' => '<h2>Titre</h2>'],
        ['label' => 'Display Heading', 'html' => '<h1 class="display-5">Titre display</h1>'],
        ['label' => 'Lead', 'html' => '<p class="lead">Texte introductif.</p>'],
        ['label' => 'Petit texte', 'html' => '<small class="text-muted">Texte secondaire.</small>'],
    ],

    'blocks' => [
        ['label' => 'Liste UL', 'html' => '<ul><li>Element 1</li><li>Element 2</li><li>Element 3</li></ul>'],
        ['label' => 'Liste OL', 'html' => '<ol><li>Premier</li><li>Deuxieme</li><li>Troisieme</li></ol>'],
        ['label' => 'Liste Inline', 'html' => '<ul class="list-inline"><li class="list-inline-item">Tag 1</li><li class="list-inline-item">Tag 2</li><li class="list-inline-item">Tag 3</li></ul>'],
        ['label' => 'List Group', 'html' => '<ul class="list-group"><li class="list-group-item">A</li><li class="list-group-item">B</li><li class="list-group-item">C</li></ul>'],
        ['label' => 'Table Base', 'html' => '<table class="table"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table>'],
        ['label' => 'Table Striped', 'html' => '<table class="table table-striped"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table>'],
        ['label' => 'Table Bordered', 'html' => '<table class="table table-bordered"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table>'],
        ['label' => 'Table Responsive', 'html' => '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>Colonne</th></tr></thead><tbody><tr><td>1</td><td>Valeur</td></tr></tbody></table></div>'],
    ],

    'allowed_iframe_hosts' => [],

    'allowed_css_classes' => [
        'alert', 'alert-info', 'alert-warning', 'alert-danger', 'alert-success',
        'btn', 'btn-primary', 'btn-secondary', 'btn-outline-primary',
        'btn-sm', 'badge',
        'btn-group',
        'card', 'card-body', 'card-title', 'card-text', 'card-header',
        'accordion', 'accordion-item', 'accordion-header', 'accordion-button', 'accordion-collapse', 'accordion-body', 'accordion-flush', 'collapsed',
        'collapse', 'show',
        'row', 'col', 'g-2', 'g-3',
        'col-md-3', 'col-md-4', 'col-md-6', 'col-lg-3', 'col-lg-4', 'col-lg-6',
        'p-3', 'p-4', 'mb-2', 'mb-3', 'mb-4', 'rounded', 'rounded-0', 'rounded-1', 'rounded-2', 'rounded-3', 'rounded-pill',
        'border', 'border-0', 'border-1', 'border-2', 'border-3', 'border-4', 'border-5',
        'border-primary', 'border-secondary', 'border-success', 'border-warning', 'border-danger', 'border-info',
        'bg-light', 'text-muted',
        'shadow', 'shadow-sm', 'shadow-lg',
        'blockquote', 'blockquote-footer', 'lead',
        'table', 'table-striped', 'table-bordered', 'table-hover', 'table-dark',
        'table-borderless', 'table-sm', 'table-responsive',
        'list-inline', 'list-inline-item', 'list-group', 'list-group-item', 'active', 'list-unstyled',
        'd-flex', 'd-block', 'gap-2', 'flex-wrap', 'form-check-input', 'me-2', 'mx-auto',
        'float-start', 'float-end', 'align-top', 'align-middle', 'align-bottom',
        'display-5', 'display-6',
        'text-start', 'text-center', 'text-end',
        'bg-dark', 'text-light', 'bg-primary', 'bg-secondary', 'bg-success', 'bg-warning', 'bg-danger', 'bg-info',
        'catmin-bookmarks-floating', 'catmin-bookmarks-auto', 'catmin-bookmarks-title', 'catmin-bookmarks-list', 'catmin-bookmarks-link', 'is-active',
    ],
];
