@extends('admin.layouts.catmin')

@section('page_title', 'Tags articles')

@section('content')
<x-admin.crud.page-header
    title="Tags articles"
    subtitle="Référentiel de tags réutilisables pour le contenu éditorial."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour articles
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body d-grid gap-4">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Nouveau tag</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('articles.tags.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="name">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <x-admin.crud.table-card title="Tags" :count="$tags->count()" :empty-colspan="4" empty-message="Aucun tag.">
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Slug</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($tags as $tag)
                <tr>
                    <td>{{ $tag->id }}</td>
                    <td>{{ $tag->name }}</td>
                    <td>{{ $tag->slug }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('articles.tags.edit', ['tag' => $tag->id]) }}">Modifier</a>
                            <form method="post" action="{{ admin_route('articles.tags.destroy', ['tag' => $tag->id]) }}" onsubmit="return confirm('Supprimer ce tag ?');">
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