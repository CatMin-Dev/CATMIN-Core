@extends('admin.layouts.catmin')

@section('page_title', 'Blog')

@section('content')
<x-admin.crud.page-header
    title="Blog"
    subtitle="Articles editoriaux plus longs et structures (distincts des news)."
>
    <a class="btn btn-primary" href="{{ admin_route('blog.create') }}">Nouvel article</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Articles"
        :count="$items->count()"
        :empty-colspan="8"
        empty-message="Aucun article blog."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Slug</th>
                <th>Extrait</th>
                <th>Statut</th>
                <th>Publication</th>
                <th>Taxo/SEO</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->slug }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($item->excerpt ?: '', 90) ?: 'n/a' }}</td>
                    <td><span class="badge {{ $item->status === 'published' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->status === 'published' ? 'Publie' : 'Brouillon' }}</span></td>
                    <td>{{ optional($item->published_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                    <td>T{{ is_array($item->taxonomy_snapshot) ? 'ok' : '-' }} / S{{ $item->seo_meta_id ?: '-' }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('blog.edit', ['blogPost' => $item->id]) }}">Modifier</a>
                            <form method="post" action="{{ admin_route('blog.toggle_status', ['blogPost' => $item->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm {{ $item->status === 'published' ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                    {{ $item->status === 'published' ? 'Depublier' : 'Publier' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
