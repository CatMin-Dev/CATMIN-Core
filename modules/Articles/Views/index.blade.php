@extends('admin.layouts.catmin')

@section('page_title', 'Articles')

@section('content')
<x-admin.crud.page-header
    title="Articles"
    subtitle="Module unifie pour les contenus editoriaux et actualites."
>
    <form method="get" action="{{ admin_route('articles.manage') }}" class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="min-width: 280px;">
            <input
                type="search"
                name="q"
                class="form-control"
                placeholder="Recherche articles..."
                value="{{ $search ?? '' }}"
            >
            <button class="btn btn-outline-secondary" type="submit" aria-label="Rechercher">
                <i class="bi bi-search"></i>
            </button>
        </div>
        @if(!empty($search))
            <a class="btn btn-sm btn-outline-light border" href="{{ admin_route('articles.manage') }}">Reset</a>
        @endif
    </form>

    @if(catmin_can('module.articles.create'))
        <a class="btn btn-primary" href="{{ admin_route('articles.create') }}">Nouvel article</a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Articles"
        :count="$items->total()"
        :empty-colspan="9"
        empty-message="Aucun article."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Type</th>
                <th>Slug</th>
                <th>Extrait</th>
                <th>Statut</th>
                <th>Publication</th>
                <th>Media/SEO</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->title }}</td>
                    <td><span class="badge {{ $item->content_type === 'news' ? 'text-bg-info' : 'text-bg-primary' }}">{{ $item->content_type === 'news' ? 'News' : 'Article' }}</span></td>
                    <td>{{ $item->slug }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($item->excerpt ?: '', 90) ?: 'n/a' }}</td>
                    <td><span class="badge {{ $item->status === 'published' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->status === 'published' ? 'Publie' : 'Brouillon' }}</span></td>
                    <td>{{ optional($item->published_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                    <td>M{{ $item->media_asset_id ?: '-' }} / S{{ $item->seo_meta_id ?: '-' }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            @if(catmin_can('module.articles.edit'))
                                <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('articles.edit', ['article' => $item->id]) }}">Modifier</a>
                            @endif
                            @if(catmin_can('module.articles.edit'))
                                <form method="post" action="{{ admin_route('articles.toggle_status', ['article' => $item->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm {{ $item->status === 'published' ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                    {{ $item->status === 'published' ? 'Depublier' : 'Publier' }}
                                </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    @if($items->hasPages())
        <div class="mt-3">
            <x-admin.crud.pagination :paginator="$items" />
        </div>
    @endif
</div>
@endsection
