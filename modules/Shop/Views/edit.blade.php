@extends('admin.layouts.catmin')

@section('page_title', 'Editer produit')

@section('content')
<x-admin.crud.page-header title="Editer produit" subtitle="Modifier les infos du produit {{ $product->name }}." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.shop.update', $product) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Prix</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Prix barre</label>
                    <input type="number" step="0.01" min="0" name="compare_at_price" class="form-control" value="{{ old('compare_at_price', $product->compare_at_price) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $product->slug) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Categories</label>
                    <div class="d-flex flex-wrap gap-3">
                        @php($selectedCategories = collect(old('category_ids', $product->categories->pluck('id')->all()))->map(fn ($id) => (int) $id))
                        @foreach($categories as $category)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cat_{{ $category->id }}" name="category_ids[]" value="{{ $category->id }}" @checked($selectedCategories->contains($category->id))>
                                <label class="form-check-label" for="cat_{{ $category->id }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="6" class="form-control">{{ old('description', $product->description) }}</textarea>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Stock</label>
                    <input type="number" min="0" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', $product->stock_quantity) }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Seuil alerte stock</label>
                    <input type="number" min="0" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                        <option value="inactive" @selected(old('status', $product->status) === 'inactive')>inactive</option>
                        <option value="active" @selected(old('status', $product->status) === 'active')>active</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Visibilite</label>
                    <select name="visibility" class="form-select" required>
                        @foreach($visibilityOptions as $option)
                            <option value="{{ $option }}" @selected(old('visibility', $product->visibility) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Type</label>
                    <select name="product_type" class="form-select" required>
                        <option value="physical" @selected(old('product_type', $product->product_type) === 'physical')>physical</option>
                        <option value="digital" @selected(old('product_type', $product->product_type) === 'digital')>digital</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Image path</label>
                    <input type="text" name="image_path" class="form-control" value="{{ old('image_path', $product->image_path) }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="manage_stock" name="manage_stock" value="1" @checked(old('manage_stock', $product->manage_stock))>
                        <label class="form-check-label" for="manage_stock">Gestion de stock active</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.shop.manage') }}">Retour</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
