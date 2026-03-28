@extends('admin.layouts.system')

@section('title', 'Reinitialisation mot de passe - CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-2 text-center">Reinitialisation</h1>
                    <p class="text-muted text-center">Definissez un nouveau mot de passe admin.</p>

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ admin_route('password.update') }}" method="POST" class="vstack gap-3">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $email) }}" required autocomplete="email">
                        </div>

                        <div>
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                        </div>

                        <div>
                            <label for="password_confirmation" class="form-label">Confirmation</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Reinitialiser</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
