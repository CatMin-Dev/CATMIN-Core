@extends('admin.layouts.catmin')

@section('content')
<div class="login-container d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg border-0" style="width: 100%; max-width: 450px;">
        <!-- Header -->
        <div class="card-body p-5">
            <div class="text-center mb-5">
                <img src="{{ asset('dashboard/assets/img/logo_white.png') }}" alt="CATMIN Logo" style="max-height: 60px; margin-bottom: 20px;">
                <h1 class="h3 fw-bold text-dark">CATMIN</h1>
                <p class="text-muted small">Système de Gestion Progressif</p>
            </div>

            <!-- Login Form -->
            <form action="{{ admin_route('login.submit') }}" method="POST" class="needs-validation">
                @csrf

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label fw-bold">Nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-person"></i>
                        </span>
                        <input 
                            type="text" 
                            class="form-control border-start-0 @error('username') is-invalid @enderror" 
                            id="username" 
                            name="username" 
                            placeholder="admin" 
                            required
                            autofocus
                        >
                        @error('username')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label fw-bold">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            class="form-control border-start-0 @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                        <button 
                            type="button" 
                            class="btn btn-outline-secondary border-start-0" 
                            id="togglePassword"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                        @error('password')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold mb-3">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>

                <!-- Footer Link -->
                <div class="text-center">
                    <small class="text-muted">
                        Accès administrateur sécurisé • CATMIN {{ config('app.version', 'v0.1') }}
                    </small>
                </div>
            </form>

            <!-- Alert if coming from error -->
            @if(request()->has('reason'))
                <div class="alert alert-warning alert-dismissible fade show mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Attention:</strong> Vos identifiants sont invalides, veuillez réessayer.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>

        <!-- Security Footer -->
        <div class="card-footer bg-light border-top py-3">
            <small class="text-muted d-block text-center">
                <i class="bi bi-shield-lock"></i> 
                Connexion sécurisée SSL • Données chiffrées
            </small>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    :root {
        --bs-primary: #2a3f54;
        --bs-secondary: #95a5a6;
    }

    .login-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 15px;
    }

    .card {
        backdrop-filter: blur(10px);
    }

    .input-group .form-control:focus,
    .input-group .btn:focus {
        border-color: #2a3f54;
        box-shadow: 0 0 0 0.2rem rgba(42, 63, 84, 0.25);
    }

    .btn-primary {
        background-color: #2a3f54;
        border-color: #2a3f54;
        transition: all 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #1f2d3d;
        border-color: #1f2d3d;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(42, 63, 84, 0.3);
    }

    .form-control {
        border-radius: 6px;
        padding: 12px 15px;
        font-size: 14px;
    }

    .form-label {
        font-size: 14px;
        color: #2c3e50;
    }

    .bg-light {
        background-color: #f8f9fa !important;
    }

    img[alt="CATMIN Logo"] {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
</style>

<!-- Toggle Password Visibility -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    
    if (toggleButton && passwordField) {
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword 
                ? '<i class="bi bi-eye-slash"></i>' 
                : '<i class="bi bi-eye"></i>';
        });
    }
});
</script>
@endsection
