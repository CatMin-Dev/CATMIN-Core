@extends('admin.layouts.catmin')

@section('content')
<div class="error-page d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="text-center">
        <div class="display-1 fw-bold text-danger mb-3">
            <i class="bi bi-bug"></i> 500
        </div>
        <h1 class="h2 fw-bold mb-3">Erreur Serveur</h1>
        <p class="text-muted lead mb-4">
            Une erreur interne s'est produite. Nos équipes ont été notifiées.
        </p>
        <div class="btn-group gap-3">
            <a href="{{ admin_route('preview', ['page' => 'dashboard']) }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Réessayer
            </a>
            <a href="/" class="btn btn-secondary">
                <i class="bi bi-house"></i> Accueil
            </a>
        </div>
        <div class="mt-5">
            <small class="text-muted d-block">
                <i class="bi bi-info-circle"></i>
                Référence: #{{ str_replace('-', '', substr(now()->toDateTimeString(), -19)) }}
            </small>
        </div>
    </div>
</div>

<style>
    .error-page {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        min-height: 100vh;
    }

    .error-page .display-1 {
        font-size: 128px;
        line-height: 1;
        animation: shake 2s infinite;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .btn {
        padding: 12px 30px;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background-color: #2a3f54;
        border-color: #2a3f54;
    }

    .btn-primary:hover {
        background-color: #1f2d3d;
        border-color: #1f2d3d;
        transform: translateY(-2px);
    }

    .text-muted {
        color: rgba(255,255,255,0.7) !important;
    }

    .lead {
        color: rgba(255,255,255,0.9);
    }

    h1 {
        color: white;
    }
</style>
@endsection
