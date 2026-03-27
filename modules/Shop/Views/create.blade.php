@extends('admin.layouts.catmin')

@section('page_title', 'Nouveau produit')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Nouveau produit</h1>
    <p class="text-muted mb-0">Ajouter un produit simple au catalogue.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.shop.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-8">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Prix</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', '0.00') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Slug (optionnel)</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="6" class="form-control">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                        <option value="inactive" @selected(old('status', 'inactive') === 'inactive')>inactive</option>
                        <option value="active" @selected(old('status') === 'active')>active</option>
                    </select>
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
