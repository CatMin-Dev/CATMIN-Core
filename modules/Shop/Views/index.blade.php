@extends('admin.layouts.catmin')

@section('page_title', 'Shop')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Shop</h1>
        <p class="text-muted mb-0">Catalogue des produits simples.</p>
    </div>
    @if(catmin_can('module.shop.create'))
    <a class="btn btn-primary" href="{{ route('admin.shop.create') }}">Nouveau produit</a>
    @endif
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Produits</h2>
            <span class="badge text-bg-light">{{ $products->count() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Prix</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->slug }}</td>
                            <td>{{ number_format((float) $product->price, 2, '.', ' ') }}</td>
                            <td><span class="badge {{ $product->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $product->status }}</span></td>
                            <td class="d-flex gap-2">
                                @if(catmin_can('module.shop.edit'))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.shop.edit', $product) }}">Editer</a>
                                <form method="POST" action="{{ route('admin.shop.toggle_status', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Toggle</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun produit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
