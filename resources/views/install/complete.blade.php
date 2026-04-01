<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Complètée - CATMIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, #667eea 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            margin: 20px;
        }
        
        .completion-content {
            text-align: center;
        }
        
        .completion-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .completion-content h2 {
            color: var(--primary);
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .completion-content p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .completion-checklist {
            background: #ecfdf5;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .completion-checklist li {
            color: var(--success);
            font-weight: 600;
            margin-bottom: 8px;
            list-style: none;
        }
        
        .completion-checklist li:before {
            content: "✓ ";
            margin-right: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
            color: white;
        }
        
        .version-info {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 12px;
            font-size: 12px;
            color: #6b7280;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="completion-content">
            <div class="completion-icon">🎉</div>
            <h2>Installation Réussie!</h2>
            <p>CATMIN V3 a été installé et configuré avec succès.</p>

            <ul class="completion-checklist">
                <li>Configuration base de données complète</li>
                <li>Migrations exécutées</li>
                <li>Rôles et permissions initialisés</li>
                <li>Compte administrateur créé</li>
            </ul>

            <p style="margin-top: 20px; font-weight: 600;">Vous êtes maintenant prêt à accéder à l'administration.</p>

            <div style="margin-top: 30px;">
                <form id="finalizeForm">
                    @csrf
                    <button type="button" onclick="finalize()" class="btn btn-primary">
                        Accéder au Tableau de Bord →
                    </button>
                </form>
            </div>

            <div class="version-info">
                <strong>CATMIN</strong> — Version 3.0.0<br>
                Plateforme CMS Modulaire
            </div>
        </div>
    </div>

    <script>
        function finalize() {
            fetch('{{ route("install.finalize") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    window.location.href = data.redirect || '/admin/login';
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(e => {
                alert('Erreur: ' + e.message);
            });
        }
    </script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
