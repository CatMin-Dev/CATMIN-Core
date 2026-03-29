@extends('admin.layouts.catmin')

@section('page_title', 'Documentation')

@section('content')
<x-admin.crud.page-header
    title="Documentation"
    subtitle="Aide embarquee, guides et documentation des modules CATMIN."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="{{ admin_route('docs.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="q">Rechercher dans la documentation</label>
                    <div class="input-group">
                        <input id="q" name="q" type="search" class="form-control"
                               placeholder="Mailer, templates, shop, facture..."
                               value="{{ $query }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                        @if($query)
                            <a class="btn btn-outline-secondary" href="{{ admin_route('docs.index') }}">Effacer</a>
                        @endif
                    </div>
                </div>
                @if(!empty($modules))
                <div class="col-12 col-md-2">
                    <label class="form-label" for="module_filter">Filtrer par module</label>
                    <select id="module_filter" name="module" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les modules</option>
                        @foreach($modules as $mod)
                            <option value="{{ $mod }}" @selected(($filters['module'] ?? '') === $mod)>{{ ucfirst($mod) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(!empty($versions))
                <div class="col-12 col-md-2">
                    <label class="form-label" for="version_filter">Version</label>
                    <select id="version_filter" name="version" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes</option>
                        @foreach($versions as $version)
                            <option value="{{ $version }}" @selected(($filters['version'] ?? '') === $version)>{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(!empty($statuses))
                <div class="col-12 col-md-2">
                    <label class="form-label" for="status_filter">Statut</label>
                    <select id="status_filter" name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(!empty($categories))
                <div class="col-12 col-md-2">
                    <label class="form-label" for="category_filter">Categorie</label>
                    <select id="category_filter" name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Search results --}}
    @if($results !== null)
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Résultats pour « {{ $query }} »</h2>
                <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('docs.index') }}">← Tous les docs</a>
            </div>
            @if(empty($results))
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-search fs-2 d-block mb-2"></i>
                    Aucun résultat pour « {{ $query }} ».
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach($results as $result)
                        <a href="{{ admin_route('docs.show', ['slug' => $result['slug']]) }}"
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-semibold">{{ $result['title'] }}</span>
                                    @if($result['module'])
                                        <span class="badge text-bg-secondary ms-2 small">{{ $result['module'] }}</span>
                                    @endif
                                    <span class="badge text-bg-light border ms-2 small">{{ $result['version'] }}</span>
                                    <span class="badge text-bg-light border ms-1 small">{{ $result['status'] }}</span>
                                    <span class="badge text-bg-light border ms-1 small">{{ $result['category'] }}</span>
                                    <p class="text-muted small mb-0 mt-1">{{ $result['excerpt'] }}</p>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    {{-- Doc listing --}}
    @else
        @if(empty($items))
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>
                    Aucun document disponible.
                </div>
            </div>
        @else
            @php
                $byModule = collect($items)->groupBy(function (array $doc): string {
                    $module = trim((string) ($doc['module'] ?? ''));

                    return $module !== '' ? $module : '__global__';
                });
                $global = collect($byModule->pull('__global__', []));
            @endphp

            {{-- Global docs (no module) --}}
            @if($global->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Documentation générale</h2></div>
                <div class="list-group list-group-flush">
                    @foreach($global as $doc)
                        <a href="{{ admin_route('docs.show', ['slug' => $doc['slug']]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-text me-2 text-muted"></i>{{ $doc['title'] }} <span class="badge text-bg-light border ms-2">{{ $doc['version'] }}</span> <span class="badge text-bg-light border ms-1">{{ $doc['status'] }}</span></span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Per-module docs --}}
            @foreach($byModule->sortKeys() as $modSlug => $modDocs)
            @php($modDocs = collect($modDocs))
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">
                        <i class="bi bi-puzzle me-2 text-muted"></i>{{ ucfirst($modSlug) }}
                        <a class="btn btn-xs btn-outline-secondary ms-2 py-0 px-2 small"
                           href="{{ admin_route('docs.index', ['module' => $modSlug]) }}">
                            Tout voir
                        </a>
                    </h2>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($modDocs as $doc)
                        <a href="{{ admin_route('docs.show', ['slug' => $doc['slug']]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-text me-2 text-muted"></i>{{ $doc['title'] }} <span class="badge text-bg-light border ms-2">{{ $doc['version'] }}</span> <span class="badge text-bg-light border ms-1">{{ $doc['category'] }}</span></span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif
    @endif
</div>
@endsection
