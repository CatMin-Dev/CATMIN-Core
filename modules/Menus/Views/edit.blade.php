@extends('admin.layouts.catmin')

@section('page_title', 'Editer menu')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Editer menu</h1>
        <p class="text-muted mb-0">{{ $menu->name }} ({{ $menu->location }})</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.menus.manage') }}">Retour menus</a>
</header>

<div class="catmin-page-body">
    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Parametres menu</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.menus.update', $menu) }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-12">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $menu->name) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $menu->slug) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $menu->location) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select" required>
                                <option value="active" @selected(old('status', $menu->status) === 'active')>active</option>
                                <option value="inactive" @selected(old('status', $menu->status) === 'inactive')>inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Enregistrer menu</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Ajouter un item</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.menus.items.store', $menu) }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" class="form-control" value="{{ old('label') }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="url" @selected(old('type', 'url') === 'url')>url</option>
                                <option value="page" @selected(old('type') === 'page')>page</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Parent (optionnel)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">Aucun</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">#{{ $item->id }} - {{ $item->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL (si type=url)</label>
                            <input type="text" name="url" class="form-control" value="{{ old('url') }}" placeholder="/contact ou https://...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Page (si type=page)</label>
                            <select name="page_id" class="form-select">
                                <option value="">Selectionner</option>
                                @foreach($pagesOptions as $page)
                                    <option value="{{ $page->id }}">{{ $page->title }} ({{ $page->slug }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ordre</label>
                            <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Ajouter item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Items du menu</h2></div>
                <div class="table-responsive catmin-table-scroll">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>#</th><th>Label</th><th>Type</th><th>URL</th><th>Parent</th><th>Ordre</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->label }}</td>
                                    <td>{{ $item->type }}</td>
                                    <td>{{ $item->url ?: 'n/a' }}</td>
                                    <td>{{ $item->parent_id ?: '-' }}</td>
                                    <td>{{ $item->sort_order }}</td>
                                    <td><span class="badge {{ $item->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->status }}</span></td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.menus.items.toggle_status', [$menu, $item]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Toggle</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Aucun item.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
