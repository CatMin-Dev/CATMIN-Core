@extends('admin.layouts.system')

@section('title', 'Connexion admin - CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h1 class="h4 mb-1">Connexion admin</h1>
                        <p class="text-muted mb-0">Acces a l'administration CATMIN</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ admin_route('login.submit') }}" method="POST" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="username" class="form-label">Identifiant</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
                        </div>
                        <div>
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                    </form>

                    <div class="text-center mt-4"><a href="{{ url('/') }}" class="small text-decoration-none">Retour au site</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
