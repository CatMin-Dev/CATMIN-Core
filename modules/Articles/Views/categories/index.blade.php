@extends('admin.layouts.catmin')

@section('page_title', 'Catégories articles')

@section('content')
<x-admin.crud.page-header
    title="Catégories articles"
    subtitle="Hiérarchie simple pour classer les articles."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour articles
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body d-grid gap-4">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Nouvelle catégorie</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('articles.categories.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="name">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}">
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="parent_id">Parent</label>
                    <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">Aucun</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <x-admin.crud.table-card title="Catégories" :count="$categories->count()" :empty-colspan="5" empty-message="Aucune catégorie.">
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Slug</th>
                <th>Parent</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($categories as $category)
                <tr>
                    <td>{{ $category->id }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->parent?->name ?: '—' }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('articles.categories.edit', ['category' => $category->id]) }}">Modifier</a>
                            <form method="post" action="{{ admin_route('articles.categories.destroy', ['category' => $category->id]) }}" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection