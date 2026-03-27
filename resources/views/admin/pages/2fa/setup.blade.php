@extends('admin.layouts.catmin')

@section('page_title', 'Configuration 2FA')

@section('content')
<x-admin.crud.page-header
    title="Authentification a deux facteurs"
    subtitle="Securiser l'acces admin avec un second facteur TOTP."
>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    @if($enabled)
        <div class="alert alert-success d-flex align-items-center gap-2">
            <span class="fw-semibold">2FA active</span> —
            La double authentification est activee sur ce compte admin.
        </div>
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3">Desactiver la 2FA</h2>
                <p class="text-muted">
                    Pour desactiver la 2FA, retirez <code>CATMIN_2FA_ENABLED=true</code>
                    et <code>CATMIN_2FA_SECRET</code> de votre fichier <code>.env</code>,
                    puis rechargez la configuration.
                </p>
            </div>
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <span class="fw-semibold">2FA non activee</span> —
            Suis les etapes ci-dessous pour activer la 2FA.
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h2 class="h6 mb-0">Etape 1 — Scanne le QR code</h2></div>
                    <div class="card-body text-center">
                        @if($qrCodeUri)
                            <div class="mb-3">
                                <img
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUri) }}"
                                    alt="QR Code 2FA"
                                    class="border rounded"
                                    width="200" height="200"
                                >
                            </div>
                            <p class="text-muted small">
                                Scan avec Google Authenticator, Authy ou une app compatible TOTP.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h2 class="h6 mb-0">Etape 2 — Configure ton .env</h2></div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Ajoute ces deux lignes dans ton fichier <code>.env</code> :
                        </p>
                        <pre class="bg-light p-3 rounded small"><code>CATMIN_2FA_ENABLED=true
CATMIN_2FA_SECRET={{ $newSecret }}</code></pre>
                        <div class="alert alert-danger mt-3 small">
                            <strong>Important :</strong> Copie ce secret maintenant — il ne sera plus affiché une fois activé.
                            Ne partage jamais ce secret.
                        </div>
                        <p class="text-muted small mt-2">
                            Apres avoir modifie le <code>.env</code>, relance
                            <code>php artisan config:clear</code> ou recharge le serveur.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
