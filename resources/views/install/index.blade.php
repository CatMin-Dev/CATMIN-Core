<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation CATMIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
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
        
        .install-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .install-header h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .install-header p {
            color: #6b7280;
            font-size: 16px;
        }
        
        .step-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .btn-install {
            background: var(--primary);
            border: none;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-install:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
            color: white;
        }
        
        .welcome-content {
            text-align: center;
        }
        
        .welcome-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .welcome-content h2 {
            color: var(--primary);
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .welcome-content p {
            color: #6b7280;
            font-size: 15px;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .installation-steps {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            font-size: 14px;
        }
        
        .installation-steps ol {
            margin-bottom: 0;
            color: #4b5563;
        }
        
        .installation-steps li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>CATMIN</h1>
            <p>Plateforme CMS Modulaire V3</p>
        </div>

        <div class="welcome-content">
            <div class="welcome-icon">🚀</div>
            <h2>Bienvenue dans l'Installation</h2>
            <p>Cet assistant vous guidera à travers les étapes de configuration pour démarrer CATMIN.</p>
            <p>L'installation prendra environ <strong>5 minutes</strong> et ne nécessite aucune configuration manuelle.</p>

            <div class="installation-steps">
                <ol>
                    <li>Vérification système</li>
                    <li>Configuration base de données</li>
                    <li>Création compte administrateur</li>
                    <li>Initialisation</li>
                </ol>
            </div>

            <a href="{{ route('install.system-check') }}" class="btn btn-install btn-block mt-4">
                Démarrer l'Installation
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
