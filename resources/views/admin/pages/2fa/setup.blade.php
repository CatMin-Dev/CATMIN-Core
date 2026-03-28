@extends('admin.layouts.catmin')

@section('page_title', 'Configuration 2FA')

@section('content')
<x-admin.crud.page-header
    title="Authentification a deux facteurs"
    subtitle="Securiser ce compte admin avec TOTP et codes de recuperation."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    @if(!empty($recoveryCodes))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-2">Codes de recuperation (a sauvegarder maintenant)</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($recoveryCodes as $code)
                    <code class="px-2 py-1 bg-light border rounded">{{ $code }}</code>
                @endforeach
            </div>
            <div class="small text-muted mt-2">Ces codes ne seront plus affiches apres cette page.</div>
        </div>
    @endif

    @if($enabled)
        <div class="alert alert-success">2FA activee pour ce compte.</div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h2 class="h6 mb-0">Regenerer les codes de recuperation</h2></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.2fa.recovery.regenerate') }}" class="vstack gap-3">
                            @csrf
                            <div>
                                <label class="form-label" for="regenOtp">Code OTP ou code de recuperation</label>
                                <input id="regenOtp" name="otp" type="text" class="form-control @error('otp') is-invalid @enderror" required>
                                @error('otp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Regenerer</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100 border-danger">
                    <div class="card-header bg-white"><h2 class="h6 mb-0 text-danger">Desactiver la 2FA</h2></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.2fa.disable') }}" class="vstack gap-3">
                            @csrf
                            <div>
                                <label class="form-label" for="disableOtp">Code OTP ou code de recuperation</label>
                                <input id="disableOtp" name="otp" type="text" class="form-control @error('otp') is-invalid @enderror" required>
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Desactiver la 2FA pour ce compte ?');">Desactiver</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">2FA non activee pour ce compte.</div>
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h2 class="h6 mb-0">Etape 1: scanner le QR code</h2></div>
                    <div class="card-body text-center">
                        @if($qrCodeUri)
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrCodeUri) }}"
                                alt="QR Code 2FA"
                                class="border rounded"
                                width="220"
                                height="220"
                            >
                        @endif
                        <p class="small text-muted mt-3 mb-0">Compatible Google Authenticator, Authy et apps TOTP.</p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h2 class="h6 mb-0">Etape 2: confirmer l'activation</h2></div>
                    <div class="card-body">
                        <p class="small text-muted">Secret de configuration:</p>
                        <pre class="bg-light border rounded p-2 small"><code>{{ $setupSecret }}</code></pre>

                        <form method="POST" action="{{ route('admin.2fa.enable') }}" class="vstack gap-3 mt-3">
                            @csrf
                            <div>
                                <label class="form-label" for="enableOtp">Code OTP (6 chiffres)</label>
                                <input
                                    id="enableOtp"
                                    name="otp"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]{6}"
                                    maxlength="6"
                                    class="form-control @error('otp') is-invalid @enderror"
                                    required
                                >
                                @error('otp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Activer la 2FA</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
