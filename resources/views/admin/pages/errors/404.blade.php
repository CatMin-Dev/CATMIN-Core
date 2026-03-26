@extends('admin.layouts.catmin')

@section('content')
<div class="error-page d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="text-center">
        <div class="display-1 fw-bold text-warning mb-3">
            <i class="bi bi-exclamation-circle"></i> 404
        </div>
        <h1 class="h2 fw-bold mb-3">Page Non Trouvée</h1>
        <p class="text-muted lead mb-4">
            La page que vous recherchez n'existe pas ou a été supprimée.
        </p>
        <div class="btn-group gap-3">
            <a href="{{ admin_route('preview', ['page' => 'dashboard']) }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Retour au Dashboard
            </a>
            <a href="/" class="btn btn-secondary">
                <i class="bi bi-house"></i> Accueil
            </a>
        </div>
    </div>
</div>

<style>
    .error-page {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        min-height: 100vh;
    }

    .error-page .display-1 {
        font-size: 128px;
        line-height: 1;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
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
