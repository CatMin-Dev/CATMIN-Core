<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création Administrateur - Installation CATMIN</title>
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
        
        .password-requirements {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 12px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: #6b7280;
        }
        
        .requirement.met {
            color: var(--success);
        }
        
        .requirement-icon {
            display: inline-block;
            width: 16px;
            text-align: center;
            margin-right: 8px;
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
        
        .alert {
            border-radius: 8px;
            border: 1px solid;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #fef2f2;
            color: var(--danger);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #fecaca;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="step-badge">Étape 3 / 4 - Administrateur + Template</div>
        
        <h2 style="color: var(--primary); margin-bottom: 30px;">Créer le Compte Administrateur</h2>

        @if($errors->any())
            <div class="error-message">
                <strong>Erreur:</strong>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(isset($error))
            <div class="error-message">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ route('install.create-admin') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Nom Complet</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Votre nom" required value="{{ old('name') }}">
                <div class="form-hint">Minimum 3 caractères</div>
                @error('name')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Adresse Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="admin@example.com" required value="{{ old('email') }}">
                <div class="form-hint">Cet email sera utilisé pour la connexion</div>
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Mot de Passe</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="••••••••" required>
                <div class="form-hint">Minimum 12 caractères avec majuscules, minuscules et chiffres</div>
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="password-requirements">
                <strong style="color: #1f2937; display: block; margin-bottom: 10px;">Exigences du mot de passe:</strong>
                <div class="requirement" id="req-length">
                    <span class="requirement-icon">○</span>
                    Minimum 12 caractères
                </div>
                <div class="requirement" id="req-upper">
                    <span class="requirement-icon">○</span>
                    Une lettre majuscule (A-Z)
                </div>
                <div class="requirement" id="req-lower">
                    <span class="requirement-icon">○</span>
                    Une lettre minuscule (a-z)
                </div>
                <div class="requirement" id="req-number">
                    <span class="requirement-icon">○</span>
                    Un chiffre (0-9)
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirmer le Mot de Passe</label>
                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" id="password_confirmation" placeholder="••••••••" required>
                @error('password_confirmation')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Template de demarrage (optionnel)</label>
                <select class="form-control @error('template_slug') is-invalid @enderror" name="template_slug">
                    <option value="">Aucun template</option>
                    @foreach(($templates ?? []) as $template)
                        <option value="{{ $template['slug'] ?? '' }}" @selected(old('template_slug') === ($template['slug'] ?? ''))>
                            {{ $template['name'] ?? ($template['slug'] ?? 'template') }} ({{ $template['slug'] ?? 'n/a' }})
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">Le template peut precharger pages, articles, menus, blocs, settings et medias placeholders.</div>
                @error('template_slug')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;">
                    <input type="checkbox" name="template_overwrite" value="1" @checked(old('template_overwrite'))>
                    Ecraser les donnees existantes si conflits
                </label>
            </div>

            @if(is_array($latestTemplateReport ?? null))
                <div class="password-requirements">
                    <strong style="color: #1f2937; display: block; margin-bottom: 10px;">Dernier rapport template</strong>
                    <div>Template: {{ $latestTemplateReport['template']['slug'] ?? 'n/a' }}</div>
                    <div>Date: {{ $latestTemplateReport['installed_at'] ?? 'n/a' }}</div>
                </div>
            @endif

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <a href="{{ route('install.database-form') }}" class="btn btn-secondary" style="flex: 1; text-decoration: none;">
                    Retour
                </a>
                <button type="submit" class="btn btn-install" style="flex: 1;">
                    Créer & Continuer →
                </button>
            </div>
        </form>
    </div>

    <script>
        function checkPassword() {
            const password = document.getElementById('password').value;
            
            document.getElementById('req-length').classList.toggle('met', password.length >= 12);
            document.getElementById('req-upper').classList.toggle('met', /[A-Z]/.test(password));
            document.getElementById('req-lower').classList.toggle('met', /[a-z]/.test(password));
            document.getElementById('req-number').classList.toggle('met', /[0-9]/.test(password));
        }

        document.getElementById('password').addEventListener('input', checkPassword);
        checkPassword();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
