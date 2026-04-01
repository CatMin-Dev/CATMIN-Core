<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Système - Installation CATMIN</title>
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
            max-width: 600px;
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
        
        .check-item {
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 8px;
            border-left: 4px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .check-item.ok {
            background: #ecfdf5;
            border-left-color: var(--success);
        }
        
        .check-item.warning {
            background: #fffbeb;
            border-left-color: var(--warning);
        }
        
        .check-item.error {
            background: #fef2f2;
            border-left-color: var(--danger);
        }
        
        .check-label {
            font-weight: 600;
            color: #1f2937;
        }
        
        .check-status {
            font-size: 18px;
        }
        
        .check-message {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .check-item.error .check-label,
        .check-item.error .check-message {
            color: var(--danger);
        }
        
        .check-item.warning .check-label,
        .check-item.warning .check-message {
            color: #b45309;
        }
        
        .check-item.ok .check-label,
        .check-item.ok .check-message {
            color: var(--success);
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
        
        .btn-install:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
        }
        
        .summary {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary.ready {
            background: #ecfdf5;
            border-left: 4px solid var(--success);
        }
        
        .summary.blocked {
            background: #fef2f2;
            border-left: 4px solid var(--danger);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="step-badge">Étape 1 / 4 - Vérification Système</div>
        
        <h2 style="color: var(--primary); margin-bottom: 30px;">Vérification des Prérequis</h2>

        @php
            $allOk = $checks['ok'] ?? false;
            $errors = $checks['errors'] ?? [];
            $warnings = $checks['warnings'] ?? [];
        @endphp

        <div class="summary {{ $allOk ? 'ready' : 'blocked' }}">
            @if($allOk)
                <strong style="color: var(--success); font-size: 16px;">✓ Tous les prérequis sont satisfaits</strong>
            @else
                <strong style="color: var(--danger); font-size: 16px;">✗ Des problèmes doivent être résolus</strong>
            @endif
        </div>

        @foreach($checks['checks'] ?? [] as $checkName => $result)
            @php
                $status = $result['ok'] ? 'ok' : ($result['status'] === 'warning' ? 'warning' : 'error');
                $icon = $status === 'ok' ? '✓' : ($status === 'warning' ? '⚠' : '✗');
            @endphp
            
            <div class="check-item {{ $status }}">
                <div style="flex: 1;">
                    <div class="check-label">{{ ucfirst(str_replace('_', ' ', $checkName)) }}</div>
                    <div class="check-message">{{ $result['message'] ?? '' }}</div>
                </div>
                <div class="check-status">{{ $icon }}</div>
            </div>
        @endforeach

        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <a href="{{ route('install.index') }}" class="btn btn-secondary" style="flex: 1; text-decoration: none;">
                Retour
            </a>
            @if($allOk)
                <a href="{{ route('install.database-form') }}" class="btn btn-install" style="flex: 1; text-decoration: none;">
                    Continuer →
                </a>
            @else
                <button class="btn btn-install" disabled style="flex: 1;">
                    Blocages détectés
                </button>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
