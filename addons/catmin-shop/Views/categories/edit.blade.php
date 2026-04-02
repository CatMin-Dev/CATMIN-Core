@extends('admin.layouts.catmin')

@section('page_title', 'Editer categorie')

@section('content')
<x-admin.crud.page-header title="Modifier categorie" subtitle="Ajuste la hierarchie et le tri." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ admin_route('shop.categories.update', ['category' => $category->id]) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nom</label>
                    <input name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug', $category->slug) }}">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Parent</label>
                    <select name="parent_id" class="form-select">
                        <option value="">Aucun</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Tri</label>
                    <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                        <label class="form-check-label" for="is_active">Categorie active</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('shop.categories.index') }}">Retour</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
