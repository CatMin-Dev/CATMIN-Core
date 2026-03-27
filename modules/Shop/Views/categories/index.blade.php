@extends('admin.layouts.catmin')

@section('page_title', 'Categories shop')

@section('content')
<x-admin.crud.page-header title="Categories shop" subtitle="Hierarchie simple pour structurer les produits." />

<div class="catmin-page-body">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.manage') }}">Produits</a>
        <a class="btn btn-sm btn-secondary" href="{{ admin_route('shop.categories.index') }}">Categories</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.orders.index') }}">Commandes</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.customers.index') }}">Clients</a>
    </div>
    <x-admin.crud.flash-messages />

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Nouvelle categorie</h2></div>
                <div class="card-body">
                    <form method="post" action="{{ admin_route('shop.categories.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Nom</label>
                            <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slug</label>
                            <input name="slug" class="form-control" value="{{ old('slug') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Parent</label>
                            <select name="parent_id" class="form-select">
                                <option value="">Aucun</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tri</label>
                            <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Creer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Categories</h2>
                    <span class="badge text-bg-light">{{ $categories->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>Nom</th><th>Parent</th><th>Produits</th><th>Etat</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->parent?->name ?: '—' }}</td>
                                <td>{{ $category->products()->count() }}</td>
                                <td><span class="badge {{ $category->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('shop.categories.edit', ['category' => $category->id]) }}">Modifier</a>
                                        <form method="post" action="{{ admin_route('shop.categories.destroy', ['category' => $category->id]) }}" onsubmit="return confirm('Supprimer cette categorie ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune categorie.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
