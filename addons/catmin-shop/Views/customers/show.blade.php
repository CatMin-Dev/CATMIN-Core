@extends('admin.layouts.catmin')

@section('page_title', 'Client shop')

@section('content')
<x-admin.crud.page-header title="Client : {{ $customer->fullName() }}" subtitle="Historique commandes et factures." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <h2 class="h6">Informations</h2>
                <p class="mb-1"><strong>Email:</strong> {{ $customer->email }}</p>
                <p class="mb-1"><strong>Telephone:</strong> {{ $customer->phone ?: '—' }}</p>
                <p class="mb-0"><strong>Factures:</strong> {{ $customer->invoices->count() }}</p>
            </div></div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card"><div class="card-header bg-white"><h2 class="h6 mb-0">Commandes</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Commande</th><th>Statut</th><th>Total</th><th></th></tr></thead><tbody>@forelse($customer->orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->status }}</td><td>{{ number_format((float) $order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.show', ['order' => $order->id]) }}">Voir</a></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Aucune commande.</td></tr>@endforelse</tbody></table></div></div>
        </div>
    </div>
</div>
@endsection
