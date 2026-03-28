@extends('admin.layouts.system')

@section('title', 'Mot de passe oublie - CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-2 text-center">Mot de passe oublie</h1>
                    <p class="text-muted text-center">Entrez votre email admin pour recevoir un lien temporaire.</p>

                    @if(session('status'))
                        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ admin_route('password.email') }}" method="POST" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autocomplete="email">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Envoyer le lien</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="{{ admin_route('login') }}" class="small text-decoration-none">Retour a la connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
