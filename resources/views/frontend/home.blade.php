<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }} - Frontend Libre</title>
    <style>
        :root {
            --bg: #f4efe7;
            --ink: #18222d;
            --accent: #bf5b04;
            --accent-soft: #f2c48d;
            --panel: rgba(255, 255, 255, 0.82);
            --line: rgba(24, 34, 45, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(191, 91, 4, 0.18), transparent 28%),
                radial-gradient(circle at right, rgba(24, 34, 45, 0.08), transparent 25%),
                var(--bg);
        }

        .page-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        .hero {
            display: grid;
            gap: 24px;
            grid-template-columns: 2fr 1fr;
            align-items: stretch;
            margin-bottom: 32px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(24, 34, 45, 0.08);
            backdrop-filter: blur(12px);
        }

        .hero-copy {
            padding: 36px;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 18px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(191, 91, 4, 0.12);
            color: var(--accent);
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1, h2 {
            margin: 0 0 12px;
            line-height: 1.05;
        }

        h1 {
            font-size: clamp(2.4rem, 4vw, 4.8rem);
        }

        p {
            line-height: 1.7;
            margin: 0;
        }

        .hero-copy p {
            max-width: 56ch;
            font-size: 1.05rem;
        }

        .hero-side {
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(160deg, rgba(24, 34, 45, 0.95), rgba(61, 84, 92, 0.92));
            color: #f8f4ef;
        }

        .hero-side strong {
            display: block;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.75;
            margin-bottom: 10px;
        }

        .hero-side code {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff7ee;
        }

        .grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .card {
            padding: 24px;
        }

        .card h2 {
            font-size: 1.15rem;
        }

        .card ul {
            margin: 14px 0 0;
            padding-left: 18px;
            line-height: 1.8;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .meta span {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(24, 34, 45, 0.06);
            font-size: 0.92rem;
        }

        @media (max-width: 900px) {
            .hero,
            .grid {
                grid-template-columns: 1fr;
            }

            .hero-copy,
            .hero-side,
            .card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <section class="hero">
            <article class="panel hero-copy">
                <span class="eyebrow">Frontend Libre CATMIN</span>
                <h1>{{ $siteName }}</h1>
                <p>
                    Cette page pose une base Laravel propre pour le futur frontend public, sans remplacer
                    le frontend PHP existant. Elle montre quelles données CATMIN pourront être consommées
                    depuis une couche publique légère, lisible et sans framework lourd imposé.
                </p>
                <div class="meta">
                    <span>URL publique: {{ $siteUrl }}</span>
                    <span>Route Laravel: /{{ $frontendConfig['path'] }}</span>
                    <span>Theme: {{ $frontendConfig['theme'] }}</span>
                </div>
            </article>
            <aside class="panel hero-side">
                <div>
                    <strong>Separation claire</strong>
                    <p>Admin et frontend restent dissociés. L’admin continue sa migration Laravel pendant que le frontend public obtient sa propre base progressive.</p>
                </div>
                <div>
                    <strong>Etat actuel</strong>
                    <code>{{ $enabledModules->count() }} module(s) actif(s)</code>
                </div>
            </aside>
        </section>

        <section class="grid">
            <article class="panel card">
                <h2>Settings</h2>
                <p>Les paramètres publics et globaux sont déjà accessibles via le système de settings centralisés.</p>
                <ul>
                    @foreach($siteSettings as $key => $value)
                        <li>{{ $key }}: {{ $value }}</li>
                    @endforeach
                </ul>
            </article>

            <article class="panel card">
                <h2>Contenus à venir</h2>
                <p>Le frontend Laravel est préparé pour consommer ensuite les pages, contenus et menus dès que leur modèle de données sera stabilisé.</p>
                <ul>
                    <li>Pages statiques et pages CMS</li>
                    <li>Menus publics et navigation secondaire</li>
                    <li>Blocs de contenu et listes de modules</li>
                </ul>
            </article>

            <article class="panel card">
                <h2>Modules</h2>
                <p>Les modules actifs pourront injecter des données ou des blocs publics sans imposer de couplage direct à l’admin.</p>
                <ul>
                    @forelse($enabledModules as $module)
                        <li>{{ $module->name ?? ucfirst($module->slug) }} ({{ $module->version ?? 'n/a' }})</li>
                    @empty
                        <li>Aucun module public actif pour le moment.</li>
                    @endforelse
                </ul>
            </article>
        </section>
    </div>
</body>
</html>
