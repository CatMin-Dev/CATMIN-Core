@extends('admin.layouts.catmin')

@section('page_title', 'Commandes shop')

@section('content')
<x-admin.crud.page-header title="Commandes shop" subtitle="Workflow pending → paid → processing → shipped → completed." >
    <a class="btn btn-primary" href="{{ admin_route('shop.orders.create') }}">Nouvelle commande</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <x-admin.crud.flash-messages />
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0">Commandes</h2><span class="badge text-bg-light">{{ $orders->total() }}</span></div>
        <div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>Numero</th><th>Client</th><th>Statut</th><th>Total</th><th>Articles</th><th class="text-end">Actions</th></tr></thead><tbody>@forelse($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->customer_email }}</div></td><td><span class="badge text-bg-secondary">{{ $order->status }}</span></td><td>{{ number_format((float) $order->grand_total, 2, '.', ' ') }} {{ $order->currency }}</td><td>{{ $order->items->sum('quantity') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.show', ['order' => $order->id]) }}">Voir</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune commande.</td></tr>@endforelse</tbody></table></div>
        @if($orders->hasPages())<div class="card-body">{{ $orders->links() }}</div>@endif
    </div>
</div>
@endsection
