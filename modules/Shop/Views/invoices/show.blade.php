@php($snapshotMode = ($renderMode ?? 'page') === 'snapshot')
@if(!$snapshotMode)
@extends('admin.layouts.catmin')

@section('page_title', 'Facture shop')

@section('content')
<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
@endif
    <div class="card {{ $snapshotMode ? '' : 'shadow-sm' }}">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="h3 mb-1">Facture {{ $invoice?->invoice_number ?? 'brouillon' }}</h1>
                    <div class="text-muted">CATMIN Shop</div>
                </div>
                <div class="text-end small text-muted">
                    <div>Emise le {{ $invoice?->issued_on?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                    <div>Echeance {{ $invoice?->due_on?->format('d/m/Y') ?? now()->addDays(15)->format('d/m/Y') }}</div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6"><h2 class="h6">Client</h2><p class="mb-1">{{ $customer?->fullName() ?: $order->customer_name }}</p><p class="mb-1">{{ $customer?->email ?: $order->customer_email }}</p><p class="mb-0">{{ $customer?->phone ?: '—' }}</p></div>
                <div class="col-12 col-md-6 text-md-end"><h2 class="h6">Commande</h2><p class="mb-1">{{ $order->order_number }}</p><p class="mb-0">Statut {{ $order->status }}</p></div>
            </div>
            <div class="table-responsive mb-4"><table class="table table-bordered align-middle mb-0"><thead class="table-light"><tr><th>Produit</th><th>Qté</th><th>PU</th><th>Total</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) $item->unit_price, 2, '.', ' ') }} {{ $order->currency }}</td><td>{{ number_format((float) $item->line_total, 2, '.', ' ') }} {{ $order->currency }}</td></tr>@endforeach</tbody></table></div>
            <div class="row justify-content-end"><div class="col-12 col-md-5"><table class="table table-sm mb-0"><tr><th>Sous-total</th><td class="text-end">{{ number_format((float) $order->subtotal, 2, '.', ' ') }} {{ $order->currency }}</td></tr><tr><th>TVA</th><td class="text-end">{{ number_format((float) $order->tax_total, 2, '.', ' ') }} {{ $order->currency }}</td></tr><tr><th>Livraison</th><td class="text-end">{{ number_format((float) $order->shipping_total, 2, '.', ' ') }} {{ $order->currency }}</td></tr><tr><th>Total</th><td class="text-end fw-bold">{{ number_format((float) $order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</td></tr></table></div></div>
        </div>
    </div>
@if(!$snapshotMode)
</div>
@endsection
@endif
