@extends('admin.layouts.catmin')

@section('page_title', 'Media')

@section('content')
<x-admin.crud.page-header
    title="Media"
    subtitle="Fichiers reutilisables pour Pages, News, Blog, Shop."
>
    <a class="btn btn-primary" href="{{ admin_route('media.create') }}">
        <i class="bi bi-upload me-1"></i>Uploader un fichier
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Bibliotheque media"
        :count="$assets->count()"
        :empty-colspan="8"
        empty-message="Aucun media televerse."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Apercu</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Taille</th>
                <th>Alt</th>
                <th>Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($assets as $asset)
                @php($previewUrl = $mediaService->previewUrl($asset))
                <tr>
                    <td>{{ $asset->id }}</td>
                    <td>
                        @if($previewUrl)
                            <img src="{{ $previewUrl }}" alt="Apercu" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                        @else
                            <span class="badge text-bg-light">{{ strtoupper($asset->extension ?: 'file') }}</span>
                        @endif
                    </td>
                    <td>
                        <p class="mb-0 fw-semibold">{{ $asset->original_name }}</p>
                        <p class="small text-muted mb-0">{{ $asset->filename }}</p>
                    </td>
                    <td>{{ $asset->mime_type ?: 'n/a' }}</td>
                    <td>{{ $mediaService->humanSize((int) $asset->size_bytes) }}</td>
                    <td>{{ $asset->alt_text ?: 'n/a' }}</td>
                    <td>{{ optional($asset->created_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('media.edit', ['asset' => $asset->id]) }}">Modifier</a>
                            <form method="post" action="{{ admin_route('media.destroy', ['asset' => $asset->id]) }}" onsubmit="return confirm('Supprimer ce media ?');">
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
