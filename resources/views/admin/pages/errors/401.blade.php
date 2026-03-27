@extends('admin.layouts.system')

@section('title', '401 - Non authentifie | CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5 text-center">
                    <p class="display-3 fw-bold text-secondary mb-2">401</p>
                    <h1 class="h3 mb-3">Non authentifie</h1>
                    <p class="text-muted mb-4">Tu dois etre connecte pour acceder a cette ressource.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="{{ admin_route('login') }}" class="btn btn-primary">Se connecter</a>
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Retour</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
