@extends('admin.layouts.system')

@section('title', 'Verification 2FA - CATMIN')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h1 class="h4 mb-1">Verification en deux etapes</h1>
                        <p class="text-muted mb-0">Entre un code OTP (6 chiffres) ou un code de recuperation.</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('admin.2fa.verify.submit') }}" method="POST" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="otp" class="form-label">Code OTP ou code de recuperation</label>
                            <input
                                type="text"
                                class="form-control form-control-lg text-center @error('otp') is-invalid @enderror"
                                id="otp"
                                name="otp"
                                maxlength="64"
                                autocomplete="one-time-code"
                                autofocus
                                required
                            >
                            @error('otp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Confirmer</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="{{ route('admin.login') }}" class="small text-decoration-none text-muted">Annuler et se déconnecter</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
