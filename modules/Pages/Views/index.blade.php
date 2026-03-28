@extends('admin.layouts.catmin')

@section('page_title', 'Pages')

@section('content')
<x-admin.crud.page-header
    title="Pages"
    subtitle="Gestion des pages simples du frontend CATMIN."
>
    <form method="get" action="{{ admin_route('pages.manage') }}" class="d-flex gap-2 align-items-center">
        <input
            type="search"
            name="q"
            class="form-control form-control-sm"
            placeholder="Recherche pages..."
            value="{{ $search ?? '' }}"
            style="min-width: 240px;"
        >
        <button class="btn btn-sm btn-outline-secondary" type="submit">
            <i class="bi bi-search me-1"></i>Rechercher
        </button>
        @if(!empty($search))
            <a class="btn btn-sm btn-outline-light border" href="{{ admin_route('pages.manage') }}">Reset</a>
        @endif
    </form>

    @if(catmin_can('module.pages.create'))
        <a class="btn btn-primary" href="{{ admin_route('pages.create') }}">
            <i class="bi bi-plus-circle me-1"></i>Nouvelle page
        </a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Pages publiees et brouillons"
        :count="$pages->count()"
        :empty-colspan="7"
        empty-message="Aucune page pour le moment."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Slug</th>
                <th>Statut</th>
                <th>Publication</th>
                <th>Maj</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($pages as $page)
                <tr>
                    <td>{{ $page->id }}</td>
                    <td>{{ $page->title }}</td>
                    <td>{{ $page->slug }}</td>
                    <td>
                        <span class="badge {{ $page->status === 'published' ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $page->status === 'published' ? 'Publie' : 'Brouillon' }}
                        </span>
                    </td>
                    <td>{{ optional($page->published_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                    <td>{{ optional($page->updated_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            @if(catmin_can('module.pages.edit'))
                                <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('pages.edit', ['page' => $page->id]) }}">
                                    <i class="bi bi-pencil-square me-1"></i>Modifier
                                </a>
                                <form method="post" action="{{ admin_route('pages.toggle_status', ['page' => $page->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm {{ $page->status === 'published' ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                    <i class="bi {{ $page->status === 'published' ? 'bi-pause-circle' : 'bi-check2-circle' }} me-1"></i>
                                    {{ $page->status === 'published' ? 'Depublier' : 'Publier' }}
                                </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
