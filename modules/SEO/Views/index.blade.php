@extends('admin.layouts.catmin')

@section('page_title', 'SEO')

@section('content')
<x-admin.crud.page-header
    title="SEO"
    subtitle="Base simple de metadonnees SEO reutilisables."
>
    <a class="btn btn-primary" href="{{ admin_route('seo.create') }}">Nouvelle entree SEO</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Entrees SEO"
        :count="$records->count()"
        :empty-colspan="7"
        empty-message="Aucune entree SEO."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Cible</th>
                <th>Meta title</th>
                <th>Description</th>
                <th>Robots</th>
                <th>Canonical</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record->id }}</td>
                    <td>{{ $record->target_type && $record->target_id ? $record->target_type . '#' . $record->target_id : 'Global' }}</td>
                    <td>{{ $record->meta_title ?: 'n/a' }}</td>
                    <td>{{ $record->meta_description ? \Illuminate\Support\Str::limit($record->meta_description, 80) : 'n/a' }}</td>
                    <td>{{ $record->meta_robots ?: 'n/a' }}</td>
                    <td>{{ $record->canonical_url ?: 'n/a' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('seo.edit', ['seoMeta' => $record->id]) }}">Modifier</a></td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
