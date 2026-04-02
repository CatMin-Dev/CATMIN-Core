@extends('admin.layouts.catmin')

@section('page_title', 'Commande shop')

@section('content')
<x-admin.crud.page-header title="Commande {{ $order->order_number }}" subtitle="Suivi client, statut, stock et facture." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <x-admin.crud.flash-messages />
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card mb-4"><div class="card-body">
                <h2 class="h6">Resume</h2>
                <p class="mb-1"><strong>Client:</strong> {{ $order->customer_name }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ $order->customer_email }}</p>
                <p class="mb-1"><strong>Statut:</strong> <span class="badge text-bg-secondary">{{ $order->status }}</span></p>
                <p class="mb-0"><strong>Total:</strong> {{ number_format((float) $order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</p>
            </div></div>
            @if(!empty($allowedTransitions))
            <div class="card mb-4"><div class="card-header bg-white"><h2 class="h6 mb-0">Transition de statut</h2></div><div class="card-body"><form method="post" action="{{ admin_route('shop.orders.transition', ['order' => $order->id]) }}" class="d-flex gap-2">@csrf @method('PATCH') <select name="status" class="form-select">@foreach($allowedTransitions as $transition)<option value="{{ $transition }}">{{ $transition }}</option>@endforeach</select><button class="btn btn-primary" type="submit">Appliquer</button></form></div></div>
            @endif
            @if($order->invoice)
            <div class="card"><div class="card-body"><h2 class="h6">Facture</h2><a class="btn btn-outline-secondary btn-sm" href="{{ admin_route('shop.invoices.show', ['invoice' => $order->invoice->id]) }}">Voir la facture</a></div></div>
            @endif
        </div>
        <div class="col-12 col-xl-8">
            <div class="card"><div class="card-header bg-white"><h2 class="h6 mb-0">Articles</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Produit</th><th>SKU</th><th>Qté</th><th>PU</th><th>Total</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->product_sku ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) $item->unit_price, 2, '.', ' ') }}</td><td>{{ number_format((float) $item->line_total, 2, '.', ' ') }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="4" class="text-end">Sous-total</th><th>{{ number_format((float) $order->subtotal, 2, '.', ' ') }}</th></tr><tr><th colspan="4" class="text-end">TVA</th><th>{{ number_format((float) $order->tax_total, 2, '.', ' ') }}</th></tr><tr><th colspan="4" class="text-end">Livraison</th><th>{{ number_format((float) $order->shipping_total, 2, '.', ' ') }}</th></tr><tr><th colspan="4" class="text-end">Total</th><th>{{ number_format((float) $order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</th></tr></tfoot></table></div></div>
        </div>
    </div>
</div>
@endsection
