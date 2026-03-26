@extends('admin.layouts.system')

@section('title', '404 - Page Not Found | CATMIN')
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
                        <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 6rem;"></i>
                        <h1 class="display-1 fw-bold text-primary mb-0">404</h1>
                    </div>

                    <div class="mb-4">
                        <h2 class="h3 text-dark mb-3">Page Not Found</h2>
                        <p class="text-muted lead">Sorry, the page you are looking for doesn't exist or has been moved.</p>
                    </div>

                    <div class="d-grid gap-2 d-md-block mb-4">
                        <a href="{{ admin_route('preview', ['page' => 'dashboard']) }}" class="btn btn-primary btn-lg me-md-2">
                            <i class="fas fa-home me-2"></i>Go Home
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="history.back()">
                            <i class="fas fa-arrow-left me-2"></i>Go Back
                        </button>
                    </div>

                    <div class="border-top pt-4">
                        <h5 class="text-muted mb-3">Or search for what you need:</h5>
                        <form class="d-flex justify-content-center" action="{{ admin_route('preview') }}" method="GET">
                            <div class="input-group search-input-group" style="max-width: 400px;">
                                <input type="text" class="form-control" name="page" placeholder="Search pages, content..." aria-label="Search">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
