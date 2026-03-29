@php
    $snapshotMode = ($renderMode ?? 'page') === 'snapshot';
    $settings = $settings ?? null;
    $currency = $order->currency ?: ($settings?->currency ?? 'EUR');
    $dueDate = $invoice?->due_on ?? now()->addDays($settings?->payment_terms_days ?? 30);
@endphp
@if(!$snapshotMode)
@extends('admin.layouts.catmin')

@section('page_title', 'Facture {{ $invoice?->invoice_number ?? "brouillon" }}')

@section('content')
<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Catégories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
        @if($invoice)
            <a class="btn btn-sm btn-outline-primary" href="{{ admin_route('shop.invoices.pdf', ['invoice' => $invoice->id]) }}">
                <i class="bi bi-file-earmark-pdf me-1"></i> Télécharger PDF
            </a>
        @endif
        @if(catmin_can('module.shop.config'))
        <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ admin_route('shop.invoices.settings') }}">
            <i class="bi bi-sliders me-1"></i> Paramètres facture
        </a>
        @endif
    </div>
@endif

    <div class="card {{ $snapshotMode ? '' : 'shadow-sm' }}">
        <div class="card-body p-4 p-lg-5">

            {{-- Header: logo + title + dates --}}
            <div class="d-flex justify-content-between align-items-start mb-4 gap-3">
                <div>
                    @if($settings?->company_logo_url)
                        <img src="{{ $settings->company_logo_url }}" alt="{{ $settings->company_name }}" class="mb-2" style="max-height:50px">
                    @endif
                    <h1 class="h3 mb-0">Facture {{ $invoice?->invoice_number ?? 'brouillon' }}</h1>
                </div>
                <div class="text-end small text-muted">
                    <div>Émise le {{ $invoice?->issued_on?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                    <div>Échéance le {{ $dueDate->format('d/m/Y') }}</div>
                </div>
            </div>

            {{-- Seller / Client --}}
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6">
                    <h2 class="h6 text-muted text-uppercase small mb-1">Émetteur</h2>
                    @if($settings && $settings->company_name)
                        <p class="mb-1 fw-bold">{{ $settings->company_name }}</p>
                        @if($settings->company_address)
                            <p class="mb-1 small">{!! nl2br(e($settings->company_address)) !!}</p>
                        @endif
                        @if($settings->company_siret)
                            <p class="mb-0 small">SIRET : {{ $settings->company_siret }}</p>
                        @endif
                        @if($settings->company_vat)
                            <p class="mb-0 small">TVA : {{ $settings->company_vat }}</p>
                        @endif
                        @if($settings->company_email)
                            <p class="mb-0 small">{{ $settings->company_email }}</p>
                        @endif
                        @if($settings->company_phone)
                            <p class="mb-0 small">{{ $settings->company_phone }}</p>
                        @endif
                    @else
                        <p class="text-muted small fst-italic">
                            Renseigner les informations société dans
                            @if(!$snapshotMode)
                                <a href="{{ admin_route('shop.invoices.settings') }}">Paramètres facture</a>.
                            @else
                                Paramètres facture.
                            @endif
                        </p>
                    @endif
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <h2 class="h6 text-muted text-uppercase small mb-1">Client</h2>
                    <p class="mb-1 fw-bold">{{ $customer?->fullName() ?: $order->customer_name }}</p>
                    <p class="mb-1 small">{{ $customer?->email ?: $order->customer_email }}</p>
                    @if($customer?->phone)
                        <p class="mb-0 small">{{ $customer->phone }}</p>
                    @endif
                    <hr class="my-2">
                    <p class="mb-1 small">Commande : <strong>{{ $order->order_number }}</strong></p>
                    <p class="mb-0 small">Statut : {{ $order->status }}</p>
                </div>
            </div>

            {{-- Items table --}}
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Produit</th><th class="text-center">Qté</th><th class="text-end">Prix unit.</th><th class="text-end">Total HT</th></tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                            <td class="text-end">{{ number_format((float) $item->line_total, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="row justify-content-end">
                <div class="col-12 col-md-5">
                    <table class="table table-sm mb-0">
                        <tr><th>Sous-total HT</th><td class="text-end">{{ number_format((float) $order->subtotal, 2, ',', ' ') }} {{ $currency }}</td></tr>
                        <tr><th>TVA</th><td class="text-end">{{ number_format((float) $order->tax_total, 2, ',', ' ') }} {{ $currency }}</td></tr>
                        <tr><th>Livraison</th><td class="text-end">{{ number_format((float) $order->shipping_total, 2, ',', ' ') }} {{ $currency }}</td></tr>
                        <tr class="table-light fw-bold"><th>Total TTC</th><td class="text-end">{{ number_format((float) $order->grand_total, 2, ',', ' ') }} {{ $currency }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- IBAN + footer --}}
            @if($settings?->company_iban || $settings?->invoice_footer)
                <hr class="mt-4">
                @if($settings->company_iban)
                    <p class="small mb-1"><strong>Règlement par virement :</strong> {{ $settings->company_iban }}</p>
                @endif
                @if($settings->invoice_footer)
                    <p class="small text-muted mb-0">{{ $settings->invoice_footer }}</p>
                @endif
            @endif

        </div>
    </div>

@if(!$snapshotMode)
</div>
@endsection
@endif
