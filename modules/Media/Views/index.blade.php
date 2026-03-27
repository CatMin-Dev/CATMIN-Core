@extends('admin.layouts.catmin')

@section('page_title', 'Media')

@section('content')
<x-admin.crud.page-header
    title="Media"
    subtitle="Fichiers reutilisables pour Pages, Articles, Shop."
>
    <a class="btn btn-primary" href="{{ admin_route('media.create') }}">
        <i class="bi bi-upload me-1"></i>Uploader
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    {{-- Dossiers --}}
    @if(count($folders ?? []) > 0 || ($currentFolder ?? '') !== '')
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <a class="btn btn-sm {{ ($currentFolder ?? '') === '' ? 'btn-secondary' : 'btn-outline-secondary' }}"
           href="{{ admin_route('media.manage') }}">
            <i class="bi bi-folder me-1"></i>Tous
        </a>
        @foreach(($folders ?? []) as $folder)
            <a class="btn btn-sm {{ ($currentFolder ?? '') === $folder ? 'btn-secondary' : 'btn-outline-secondary' }}"
               href="{{ admin_route('media.manage', ['folder' => $folder]) }}">
                <i class="bi bi-folder2-open me-1"></i>{{ $folder }}
            </a>
        @endforeach
        @if(($currentFolder ?? '') !== '')
            <span class="text-muted small ms-2">Dossier: <strong>{{ $currentFolder }}</strong></span>
        @endif
    </div>
    @endif

    <x-admin.crud.table-card
        title="Bibliotheque media"
        :count="$assets->count()"
        :empty-colspan="9"
        empty-message="Aucun media televerse."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Apercu</th>
                <th>Nom</th>
                <th>Dossier</th>
                <th>Type</th>
                <th>Taille</th>
                <th>Alt</th>
                <th>Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($assets as $asset)
                @php
                    $previewUrl = $mediaService->previewUrl($asset);
                    $assetPath = (string) $asset->path;
                    $assetFolder = '';
                    if (str_starts_with($assetPath, 'media/')) {
                        $rel = substr($assetPath, strlen('media/'));
                        $parts = explode('/', $rel);
                        $assetFolder = count($parts) > 1 ? $parts[0] : '';
                    }
                @endphp
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
                    <td>
                        @if($assetFolder !== '')
                            <a class="badge text-bg-light text-dark text-decoration-none"
                               href="{{ admin_route('media.manage', ['folder' => $assetFolder]) }}">
                                <i class="bi bi-folder me-1"></i>{{ $assetFolder }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $asset->mime_type ?: 'n/a' }}</td>
                    <td>{{ $mediaService->humanSize((int) $asset->size_bytes) }}</td>
                    <td>{{ $asset->alt_text ?: 'n/a' }}</td>
                    <td>{{ optional($asset->created_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('media.edit', ['asset' => $asset->id]) }}">Modifier</a>
                            <form method="post" action="{{ admin_route('media.destroy', ['asset' => $asset->id]) }}" onsubmit="return confirm('Supprimer définitivement « ' + '{{ addslashes($asset->original_name) }}' + ' » ?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    <i class="bi bi-trash"></i>
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
