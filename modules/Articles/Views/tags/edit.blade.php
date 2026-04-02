@extends('admin.layouts.catmin')

@section('page_title', 'Édition tag article')

@section('content')
<x-admin.crud.page-header
    title="Modifier un tag"
    subtitle="Tag #{{ $tag->id }} — {{ $tag->name }}"
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('articles.tags.index') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ admin_route('articles.tags.update', ['tag' => $tag->id]) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="name">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $tag->name) }}" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $tag->slug) }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection