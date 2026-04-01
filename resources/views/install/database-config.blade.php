<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Base de Données - Installation CATMIN</title>
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .test-button {
            background: none;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .test-button:hover {
            background: var(--primary);
            color: white;
        }
        
        .test-result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .test-result.ok {
            background: #ecfdf5;
            color: var(--success);
            border-left: 3px solid var(--success);
        }
        
        .test-result.error {
            background: #fef2f2;
            color: var(--danger);
            border-left: 3px solid var(--danger);
        }
        
        .btn-install {
            background: var(--primary);
            border: none;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-install:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
            color: white;
        }
        
        .spinner-small {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #e5e7eb;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 5px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="step-badge">Étape 2 / 4 - Configuration</div>
        
        <h2 style="color: var(--primary); margin-bottom: 30px;">Configuration Base de Données</h2>

        <form id="dbForm">
            <div class="form-group">
                <label class="form-label">Type de Connexion</label>
                <select class="form-control" name="DB_CONNECTION" id="dbConnection">
                    <option value="mysql" selected>MySQL / MariaDB</option>
                    <option value="postgres">PostgreSQL</option>
                    <option value="sqlite">SQLite</option>
                </select>
                <div class="form-hint">Sélectionnez votre système de base de données</div>
            </div>

            <div class="form-group">
                <label class="form-label">Hôte</label>
                <input type="text" class="form-control" name="DB_HOST" value="{{ $currentConfig['DB_HOST'] ?? 'localhost' }}" placeholder="localhost">
                <div class="form-hint">Adresse du serveur de base de données</div>
            </div>

            <div class="form-group">
                <label class="form-label">Port</label>
                <input type="number" class="form-control" name="DB_PORT" value="{{ $currentConfig['DB_PORT'] ?? 3306 }}" placeholder="3306">
                <div class="form-hint">Port de connexion</div>
            </div>

            <div class="form-group">
                <label class="form-label">Nom de la Base de Données</label>
                <input type="text" class="form-control" name="DB_DATABASE" value="{{ $currentConfig['DB_DATABASE'] ?? '' }}" placeholder="catmin" required>
                <div class="form-hint">Créez la base de données avant d'avancer</div>
            </div>

            <div class="form-group">
                <label class="form-label">Utilisateur</label>
                <input type="text" class="form-control" name="DB_USERNAME" value="{{ $currentConfig['DB_USERNAME'] ?? '' }}" placeholder="root">
                <div class="form-hint">Nom d'utilisateur avec accès base de données</div>
            </div>

            <div class="form-group">
                <label class="form-label">Mot de Passe</label>
                <input type="password" class="form-control" name="DB_PASSWORD" placeholder="••••••••">
                <div class="form-hint">Mot de passe de l'utilisateur</div>
            </div>

            <div style="text-align: center; margin-bottom: 20px;">
                <button type="button" class="test-button" onclick="testDatabase()">
                    Tester la Connexion
                </button>
            </div>

            <div id="testResult"></div>

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <a href="{{ route('install.system-check') }}" class="btn btn-secondary" style="flex: 1; text-decoration: none;">
                    Retour
                </a>
                <button type="submit" class="btn btn-install" style="flex: 1;">
                    Continuer →
                </button>
            </div>
        </form>
    </div>

    <script>
        function testDatabase() {
            const formData = new FormData(document.getElementById('dbForm'));
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-small"></span>Test en cours...';

            fetch('{{ route("install.test-database") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(r => r.json())
            .then(data => {
                const resultDiv = document.getElementById('testResult');
                resultDiv.innerHTML = `<div class="test-result ${data.ok ? 'ok' : 'error'}">
                    ${data.ok ? '✓' : '✗'} ${data.message}
                </div>`;
                button.disabled = false;
                button.innerHTML = 'Tester la Connexion';
            })
            .catch(e => {
                const resultDiv = document.getElementById('testResult');
                resultDiv.innerHTML = `<div class="test-result error">✗ Erreur: ${e.message}</div>`;
                button.disabled = false;
                button.innerHTML = 'Tester la Connexion';
            });
        }

        document.getElementById('dbForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const button = e.target.querySelector('button[type="submit"]');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-small"></span>Configuration...';

            fetch('{{ route("install.configure-database") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    window.location.href = '{{ route("install.admin-form") }}';
                } else {
                    alert('Erreur: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = 'Continuer →';
                }
            })
            .catch(e => {
                alert('Erreur: ' + e.message);
                button.disabled = false;
                button.innerHTML = 'Continuer →';
            });
        });
    </script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
