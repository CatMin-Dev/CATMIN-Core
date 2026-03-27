@extends('admin.layouts.system')

@section('title', '500 - Erreur interne | CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5 text-center">
                    <p class="display-3 fw-bold text-danger mb-2">500</p>
                    <h1 class="h3 mb-3">Erreur interne</h1>
                    <p class="text-muted mb-4">Une erreur s'est produite pendant le rendu. Recharge la page ou retourne au tableau de bord.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="{{ admin_route('index') }}" class="btn btn-primary">Tableau de bord</a>
                        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">Recharger</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
