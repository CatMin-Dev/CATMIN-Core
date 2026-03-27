<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName ?? 'CATMIN' }} - Maintenance</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0b1f3a, #102d4f 55%, #1a3f63);
            color: #f2f6fa;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            width: min(92vw, 620px);
            border-radius: 16px;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.24);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
        }
        h1 {
            margin-top: 0;
            margin-bottom: .6rem;
            font-size: clamp(1.4rem, 4vw, 2rem);
        }
        p {
            margin: .5rem 0;
            line-height: 1.6;
            opacity: .95;
        }
        .badge {
            display: inline-block;
            margin-bottom: .9rem;
            padding: .3rem .65rem;
            font-size: .78rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
<main class="card">
    <span class="badge">Maintenance</span>
    <h1>{{ $siteName ?? 'CATMIN' }} est temporairement indisponible</h1>
    <p>Une operation de maintenance est en cours.</p>
    <p>Merci de revenir dans quelques instants.</p>
</main>
</body>
</html>
