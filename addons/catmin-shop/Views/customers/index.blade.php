@extends('admin.layouts.catmin')

@section('page_title', 'Clients shop')

@section('content')
<x-admin.crud.page-header title="Clients shop" subtitle="Base clients reliee aux commandes et factures." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0">Clients</h2><span class="badge text-bg-light">{{ $customers->total() }}</span></div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Nom</th><th>Email</th><th>Telephone</th><th>Commandes</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $customer->fullName() }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone ?: '—' }}</td>
                        <td>{{ $customer->orders_count }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.show', ['customer' => $customer->id]) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun client.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="card-body">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
@endsection
