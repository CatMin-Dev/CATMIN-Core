@extends('admin.layouts.system')

@section('title', 'Login - CATMIN')
@section('body_class', 'page-login login-bg')

@section('content')
<div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
    <div class="row justify-content-center w-100">
        <div class="col-xl-4 col-lg-5 col-md-6 col-sm-8 col-10">
            <div class="card shadow-lg border-0" id="loginCard">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="catmin-brand mb-3">
                            <img src="{{ asset('dashboard/assets/img/logo_color.png') }}" alt="Catmin" class="catmin-brand-logo">
                            <h3 class="catmin-brand-title mb-0">CATMIN</h3>
                            <small class="catmin-brand-subtitle">Admin Miaoude Simple</small>
                        </div>
                        <p class="text-muted">Please sign in to your account</p>
                    </div>

                    @if($errors->any() || request()->has('reason'))
                        <div class="alert alert-warning" role="alert">
                            Invalid credentials. Please try again.
                        </div>
                    @endif

                    <form action="{{ admin_route('login.submit') }}" method="POST" class="row g-3 needs-validation" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label text-muted">Username</label>
                            <div class="input-group login-input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0 @error('username') is-invalid @enderror" id="username" name="username" placeholder="Enter your username" required minlength="3" autofocus>
                                @error('username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label text-muted">Password</label>
                            <div class="input-group login-input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter your password" required minlength="8">
                                <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" disabled>
                                <label class="form-check-label text-muted" for="rememberMe">
                                    Remember me
                                </label>
                            </div>
                            <span class="text-decoration-none text-muted small">Password reset not enabled yet</span>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center mb-3">
                        <small class="text-muted">Need help accessing your account? Contact an administrator.</small>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-light opacity-75 mb-2">
                    Licence: Admin libre d'utilisations et de modification.
                </p>
                <div>
                    <a href="https://colorlib.com" target="_blank" rel="noopener noreferrer" class="text-light text-decoration-none opacity-75 me-3">Gentelella de Colorlib</a>
                    <a href="https://www.letsplay.fr" target="_blank" rel="noopener noreferrer" class="text-light text-decoration-none opacity-75">CATMIN par Let's Play &amp; Friends</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (togglePassword && passwordInput && eyeIcon) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    }
});
</script>
@endpush
