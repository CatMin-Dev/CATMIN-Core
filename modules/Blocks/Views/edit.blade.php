@extends('admin.layouts.catmin')

@section('page_title', 'Editer block')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Editer block</h1>
    <p class="text-muted mb-0">Modifier le block {{ $block->name }}.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.blocks.update', $block) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $block->name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $block->slug) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Contenu</label>
                    <textarea name="content" rows="8" class="form-control">{{ old('content', $block->content) }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $block->status) === 'active')>active</option>
                        <option value="inactive" @selected(old('status', $block->status) === 'inactive')>inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.blocks.manage') }}">Retour</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
