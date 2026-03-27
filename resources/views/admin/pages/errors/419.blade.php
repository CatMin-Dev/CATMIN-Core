@extends('admin.layouts.system')

@section('title', '419 - Session expiree | CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5 text-center">
                    <p class="display-3 fw-bold text-warning mb-2">419</p>
                    <h1 class="h3 mb-3">Session expiree</h1>
                    <p class="text-muted mb-4">
                        Ta session a expire ou le jeton CSRF est invalide.<br>
                        Recharge la page et reessaie.
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="{{ url()->previous(admin_route('login')) }}" class="btn btn-primary">Recharger</a>
                        <a href="{{ admin_route('login') }}" class="btn btn-outline-secondary">Connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
