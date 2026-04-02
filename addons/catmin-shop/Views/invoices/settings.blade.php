@extends('admin.layouts.catmin')

@section('page_title', 'Paramètres facture')

@section('content')
<x-admin.crud.page-header
    title="Paramètres facture"
    subtitle="Informations emetteur affichees sur chaque facture generee."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <form method="post" action="{{ admin_route('shop.invoices.settings.update') }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Société / Émetteur</h2>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">← Shop</a>
                        <button class="btn btn-sm btn-primary" type="submit">Enregistrer</button>
                    </div>
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="company_name">Nom société <span class="text-danger">*</span></label>
                        <input id="company_name" name="company_name" type="text" class="form-control @error('company_name') is-invalid @enderror"
                               value="{{ old('company_name', $settings->company_name) }}" required>
                        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="company_address">Adresse complète</label>
                        <textarea id="company_address" name="company_address" rows="3"
                                  class="form-control @error('company_address') is-invalid @enderror">{{ old('company_address', $settings->company_address) }}</textarea>
                        @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="company_siret">SIRET</label>
                        <input id="company_siret" name="company_siret" type="text"
                               class="form-control @error('company_siret') is-invalid @enderror"
                               value="{{ old('company_siret', $settings->company_siret) }}">
                        @error('company_siret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="company_vat">N° TVA intracommunautaire</label>
                        <input id="company_vat" name="company_vat" type="text"
                               class="form-control @error('company_vat') is-invalid @enderror"
                               value="{{ old('company_vat', $settings->company_vat) }}">
                        @error('company_vat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="company_phone">Téléphone</label>
                        <input id="company_phone" name="company_phone" type="text"
                               class="form-control @error('company_phone') is-invalid @enderror"
                               value="{{ old('company_phone', $settings->company_phone) }}">
                        @error('company_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="company_email">Email société</label>
                        <input id="company_email" name="company_email" type="email"
                               class="form-control @error('company_email') is-invalid @enderror"
                               value="{{ old('company_email', $settings->company_email) }}">
                        @error('company_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="company_iban">IBAN (virement bancaire)</label>
                        <input id="company_iban" name="company_iban" type="text"
                               class="form-control @error('company_iban') is-invalid @enderror"
                               value="{{ old('company_iban', $settings->company_iban) }}">
                        @error('company_iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="company_logo_url">URL du logo</label>
                        <input id="company_logo_url" name="company_logo_url" type="text"
                               class="form-control @error('company_logo_url') is-invalid @enderror"
                               value="{{ old('company_logo_url', $settings->company_logo_url) }}"
                               placeholder="https://...">
                        <div class="form-text">URL publique d'une image (PNG / SVG). Laisse vide pour masquer.</div>
                        @error('company_logo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="payment_terms_days">Délai de paiement (jours)</label>
                        <input id="payment_terms_days" name="payment_terms_days" type="number" min="0" max="365"
                               class="form-control @error('payment_terms_days') is-invalid @enderror"
                               value="{{ old('payment_terms_days', $settings->payment_terms_days) }}">
                        @error('payment_terms_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="currency">Devise</label>
                        <input id="currency" name="currency" type="text" maxlength="10"
                               class="form-control @error('currency') is-invalid @enderror"
                               value="{{ old('currency', $settings->currency) }}"
                               placeholder="EUR">
                        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="invoice_footer">Pied de facture</label>
                        <textarea id="invoice_footer" name="invoice_footer" rows="3"
                                  class="form-control @error('invoice_footer') is-invalid @enderror"
                                  placeholder="Mentions légales, conditions de paiement, remerciements...">{{ old('invoice_footer', $settings->invoice_footer) }}</textarea>
                        @error('invoice_footer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Annuler</a>
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Aperçu entête facture</h2></div>
                <div class="card-body">
                    <div class="border rounded p-3 bg-light small">
                        @if($settings->company_logo_url)
                            <img src="{{ $settings->company_logo_url }}" alt="Logo" class="mb-2" style="max-height:50px">
                        @endif
                        <div class="fw-bold">{{ $settings->company_name ?: 'Nom société' }}</div>
                        @if($settings->company_address)
                            <div class="text-muted">{!! nl2br(e($settings->company_address)) !!}</div>
                        @endif
                        @if($settings->company_siret)
                            <div>SIRET : {{ $settings->company_siret }}</div>
                        @endif
                        @if($settings->company_vat)
                            <div>TVA : {{ $settings->company_vat }}</div>
                        @endif
                        @if($settings->company_email)
                            <div>{{ $settings->company_email }}</div>
                        @endif
                        @if($settings->company_iban)
                            <div class="mt-2"><span class="fw-bold">IBAN :</span> {{ $settings->company_iban }}</div>
                        @endif
                    </div>

                    <hr>

                    <div class="d-flex flex-column gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="{{ admin_route('shop.invoices.settings') }}">
                            <i class="bi bi-sliders me-1"></i> Paramètres facture
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ admin_route('shop.orders.index') }}">
                            <i class="bi bi-receipt me-1"></i> Voir les commandes
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ admin_route('mailer.manage') }}">
                            <i class="bi bi-envelope me-1"></i> Paramètres email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
