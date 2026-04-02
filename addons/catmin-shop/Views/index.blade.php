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
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.shop.manage') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="active" @selected(request('status') === 'active')>active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>inactive</option>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label">Categorie</label>
                    <select name="category_id" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrer</button>
                    <a href="{{ route('admin.shop.manage') }}" class="btn btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Produits</h2>
            <span class="badge text-bg-light">{{ $products->total() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>SKU</th><th>Categories</th><th>Prix</th><th>Stock</th><th>Visibilite</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><div class="fw-semibold">{{ $product->name }}</div><div class="small text-muted">{{ $product->slug }}</div></td>
                            <td>{{ $product->sku ?: '—' }}</td>
                            <td>{{ $product->categories->pluck('name')->join(', ') ?: '—' }}</td>
                            <td>
                                <div>{{ number_format((float) $product->price, 2, '.', ' ') }}</div>
                                @if($product->compare_at_price)
                                    <div class="small text-muted text-decoration-line-through">{{ number_format((float) $product->compare_at_price, 2, '.', ' ') }}</div>
                                @endif
                            </td>
                            <td>
                                @if($product->manage_stock)
                                    <span class="badge {{ $product->isOutOfStock() ? 'text-bg-danger' : ($product->isLowStock() ? 'text-bg-warning' : 'text-bg-success') }}">{{ $product->stock_quantity }}</span>
                                @else
                                    <span class="badge text-bg-secondary">illimite</span>
                                @endif
                            </td>
                            <td>{{ $product->visibility }}</td>
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
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucun produit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="card-body">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
