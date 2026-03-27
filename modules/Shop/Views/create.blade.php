@extends('admin.layouts.catmin')

@section('page_title', 'Nouveau produit')

@section('content')
<x-admin.crud.page-header title="Nouveau produit" subtitle="Produit complet avec stock, SKU, visibilite et categories." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.shop.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Prix</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', '0.00') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Prix barre</label>
                    <input type="number" step="0.01" min="0" name="compare_at_price" class="form-control" value="{{ old('compare_at_price') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Slug (optionnel)</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Categories</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($categories as $category)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cat_{{ $category->id }}" name="category_ids[]" value="{{ $category->id }}" @checked(collect(old('category_ids', []))->contains($category->id))>
                                <label class="form-check-label" for="cat_{{ $category->id }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="6" class="form-control">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Stock</label>
                    <input type="number" min="0" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', 0) }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Seuil alerte stock</label>
                    <input type="number" min="0" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', 5) }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                        <option value="inactive" @selected(old('status', 'inactive') === 'inactive')>inactive</option>
                        <option value="active" @selected(old('status') === 'active')>active</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Visibilite</label>
                    <select name="visibility" class="form-select" required>
                        @foreach($visibilityOptions as $option)
                            <option value="{{ $option }}" @selected(old('visibility', 'public') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Type</label>
                    <select name="product_type" class="form-select" required>
                        <option value="physical" @selected(old('product_type', 'physical') === 'physical')>physical</option>
                        <option value="digital" @selected(old('product_type') === 'digital')>digital</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Image path (optionnel)</label>
                    <input type="text" name="image_path" class="form-control" value="{{ old('image_path') }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="manage_stock" name="manage_stock" value="1" @checked(old('manage_stock', true))>
                        <label class="form-check-label" for="manage_stock">Gestion de stock active</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Creer</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.shop.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
