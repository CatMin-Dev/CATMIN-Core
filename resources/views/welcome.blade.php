<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CATMIN</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="alert alert-info mb-0" role="alert">
            CATMIN est initialise. Utilise <a href="{{ admin_route('login') }}" class="alert-link">l'administration</a> ou la route frontend.
        </div>
    </main>
</body>
</html>
