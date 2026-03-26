@extends('admin.layouts.catmin')

@section('content')
<div class="error-page d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="text-center">
        <div class="display-1 fw-bold text-danger mb-3">
            <i class="bi bi-exclamation-triangle"></i> 403
        </div>
        <h1 class="h2 fw-bold mb-3">Accès Refusé</h1>
        <p class="text-muted lead mb-4">
            Vous n'avez pas la permission d'accéder à cette ressource.
        </p>
        <div class="btn-group gap-3">
            <a href="{{ admin_route('login') }}" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-left"></i> Retour à la connexion
            </a>
            <a href="/" class="btn btn-secondary">
                <i class="bi bi-house"></i> Accueil
            </a>
        </div>
    </div>
</div>

<style>
    .error-page {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .error-page .display-1 {
        font-size: 128px;
        line-height: 1;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
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
