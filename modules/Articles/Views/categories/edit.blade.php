@extends('admin.layouts.catmin')

@section('page_title', 'Édition catégorie article')

@section('content')
<x-admin.crud.page-header
    title="Modifier une catégorie"
    subtitle="Catégorie #{{ $category->id }} — {{ $category->name }}"
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('articles.categories.index') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ admin_route('articles.categories.update', ['category' => $category->id]) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="name">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug) }}">
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="parent_id">Parent</label>
                    <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">Aucun</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection