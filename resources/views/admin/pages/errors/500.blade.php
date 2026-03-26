@extends('admin.layouts.system')

@section('title', '500 - Internal Server Error | CATMIN')
@section('body_class', 'page-error error-bg')

@section('content')
<div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
    <div class="row justify-content-center w-100">
        <div class="col-lg-6 col-md-8 col-sm-10">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <div class="text-center mb-4">
                        <div class="catmin-brand mb-3">
                            <img src="{{ asset('dashboard/assets/img/logo_color.png') }}" alt="Catmin" class="catmin-brand-logo">
                            <h3 class="catmin-brand-title mb-0">CATMIN</h3>
                            <small class="catmin-brand-subtitle">Admin Miaoude Simple</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger mb-3" style="font-size: 6rem;"></i>
                        <h1 class="display-1 fw-bold text-danger mb-0">500</h1>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 text-dark mb-3">Internal Server Error</h2>
                        <p class="text-muted lead">Something went wrong on our end. We're working to fix this issue. Please try again later.</p>
                    </div>

                    <div class="d-grid gap-2 d-md-block mb-4">
                        <a href="{{ admin_route('preview', ['page' => 'dashboard']) }}" class="btn btn-primary btn-lg me-md-2">
                            <i class="fas fa-home me-2"></i>Go Home
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="location.reload()">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </div>

                    <div class="border-top pt-4">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-tools text-info fs-1 mb-2"></i>
                                    <h6 class="text-dark">Fixing</h6>
                                    <small class="text-muted">Our team is on it</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-clock text-warning fs-1 mb-2"></i>
                                    <h6 class="text-dark">Please Wait</h6>
                                    <small class="text-muted">We'll be back soon</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-headset text-success fs-1 mb-2"></i>
                                    <h6 class="text-dark">Support</h6>
                                    <small class="text-muted">We're here to help</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="text-start">
                            <h6 class="text-muted mb-2">Error Details:</h6>
                            <small class="text-muted d-block">Error ID: ERR-500-<span id="errorId"></span></small>
                            <small class="text-muted d-block">Timestamp: <span id="timestamp"></span></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const errorId = Math.random().toString(36).substring(2, 11).toUpperCase();
    const timestamp = new Date().toLocaleString();

    const errorElement = document.getElementById('errorId');
    const timeElement = document.getElementById('timestamp');

    if (errorElement) {
        errorElement.textContent = errorId;
    }

    if (timeElement) {
        timeElement.textContent = timestamp;
    }
});
</script>
@endpush
