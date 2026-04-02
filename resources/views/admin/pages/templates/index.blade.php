@extends('admin.layouts.catmin')

@section('page_title', 'Templates')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Templates installables</h1>
        <p class="text-muted mb-0">Installez ou exportez des templates de demarrage pour pages, articles, menus, blocs, settings et medias placeholders.</p>
    </div>
    <a href="{{ route('admin.settings.manage') }}" class="btn btn-outline-secondary">Retour settings</a>
</header>

<div class="catmin-page-body">
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Templates disponibles</h2>
                    <span class="badge text-bg-light border">{{ count($templates) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Template</th>
                                <th>Slug</th>
                                <th>Version</th>
                                <th>Sections</th>
                                <th>Valid</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $template['name'] ?? ($template['slug'] ?? 'template') }}</div>
                                        <div class="small text-muted">{{ $template['description'] ?? '' }}</div>
                                        @if(!empty($template['errors']))
                                            <div class="small text-danger mt-1">{{ implode(' | ', (array) $template['errors']) }}</div>
                                        @endif
                                    </td>
                                    <td><code>{{ $template['slug'] ?? 'n/a' }}</code></td>
                                    <td>{{ $template['version'] ?? '1.0.0' }}</td>
                                    <td class="small text-muted">
                                        p:{{ (int) ($template['sections']['pages'] ?? 0) }} |
                                        a:{{ (int) ($template['sections']['articles'] ?? 0) }} |
                                        m:{{ (int) ($template['sections']['menus'] ?? 0) }} |
                                        b:{{ (int) ($template['sections']['blocks'] ?? 0) }} |
                                        s:{{ (int) ($template['sections']['settings'] ?? 0) }} |
                                        mp:{{ (int) ($template['sections']['media_placeholders'] ?? 0) }}
                                    </td>
                                    <td>
                                        <span class="badge {{ !empty($template['valid']) ? 'text-bg-success' : 'text-bg-danger' }}">
                                            {{ !empty($template['valid']) ? 'OK' : 'KO' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" action="{{ route('admin.templates.install') }}" class="d-flex align-items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="slug" value="{{ $template['slug'] ?? '' }}">
                                            <label class="form-check small m-0 d-flex align-items-center gap-1">
                                                <input type="checkbox" name="overwrite" value="1" class="form-check-input">
                                                overwrite
                                            </label>
                                            <button class="btn btn-sm btn-primary" type="submit" {{ !empty($template['valid']) ? '' : 'disabled' }}>
                                                Installer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Aucun template detecte dans templates/*.template.json</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Exporter un template</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('admin.templates.export') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="slug">Slug</label>
                            <input id="slug" name="slug" class="form-control" required placeholder="my-template">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="name">Nom</label>
                            <input id="name" name="name" class="form-control" placeholder="Mon template">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary" type="submit">Exporter vers templates/slug.template.json</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Dernier rapport</h2>
                </div>
                <div class="card-body small">
                    @if(is_array($latestReport))
                        <div><strong>Template:</strong> {{ $latestReport['template']['slug'] ?? 'n/a' }}</div>
                        <div><strong>Source:</strong> {{ $latestReport['source'] ?? 'n/a' }}</div>
                        <div><strong>Date:</strong> {{ $latestReport['installed_at'] ?? 'n/a' }}</div>
                        <hr>
                        <div>Pages: {{ (int) ($latestReport['summary']['pages'] ?? 0) }}</div>
                        <div>Articles: {{ (int) ($latestReport['summary']['articles'] ?? 0) }}</div>
                        <div>Menus: {{ (int) ($latestReport['summary']['menus'] ?? 0) }}</div>
                        <div>Menu items: {{ (int) ($latestReport['summary']['menu_items'] ?? 0) }}</div>
                        <div>Blocks: {{ (int) ($latestReport['summary']['blocks'] ?? 0) }}</div>
                        <div>Settings: {{ (int) ($latestReport['summary']['settings'] ?? 0) }}</div>
                        <div>Media placeholders: {{ (int) ($latestReport['summary']['media_placeholders'] ?? 0) }}</div>
                    @else
                        <div class="text-muted">Aucun rapport template pour le moment.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
