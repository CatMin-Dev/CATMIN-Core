@extends('admin.layouts.catmin')

@section('page_title', 'Nouveau block')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Nouveau block</h1>
    <p class="text-muted mb-0">Creer un bloc reutilisable.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.blocks.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Slug (optionnel)</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Contenu</label>
                    <textarea name="content" rows="8" class="form-control">{{ old('content') }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Creer</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.blocks.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
