@extends('admin.layouts.catmin')

@section('page_title', $doc['title'])

@section('content')
<x-admin.crud.page-header
    title="{{ $doc['title'] }}"
    subtitle="{{ $doc['module'] ? 'Module : ' . ucfirst($doc['module']) : 'Documentation générale' }}"
>
    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('docs.index') }}">← Docs</a>
    @if($doc['module'])
        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('docs.index', ['module' => $doc['module']]) }}">
            Docs {{ ucfirst($doc['module']) }}
        </a>
    @endif
    @if($discordPublishEnabled)
        <form method="POST" action="{{ admin_route('docs.publish-discord', ['slug' => $doc['slug']]) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-outline-primary" type="submit">
                <i class="bi bi-discord me-1"></i>Publier Discord
            </button>
        </form>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-body p-4 p-lg-5">
            <div class="catmin-docs-body">
                {!! $doc['html'] !!}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.catmin-docs-body {
    max-width: 860px;
    font-size: 0.97rem;
    line-height: 1.75;
}
.catmin-docs-body h1 { font-size: 1.6rem; margin-top: 2rem; margin-bottom: 1rem; font-weight: 700; }
.catmin-docs-body h2 { font-size: 1.25rem; margin-top: 1.75rem; margin-bottom: 0.75rem; font-weight: 600; }
.catmin-docs-body h3 { font-size: 1.05rem; margin-top: 1.5rem; margin-bottom: 0.5rem; font-weight: 600; }
.catmin-docs-body p { margin-bottom: 1rem; }
.catmin-docs-body ul, .catmin-docs-body ol { padding-left: 1.5rem; margin-bottom: 1rem; }
.catmin-docs-body li { margin-bottom: 0.25rem; }
.catmin-docs-body code {
    background: #f3f4f6;
    padding: 0.15em 0.4em;
    border-radius: 4px;
    font-size: 0.88em;
    color: #e3116c;
}
.catmin-docs-body pre {
    background: #1e1e2e;
    color: #cdd6f4;
    padding: 1.25rem 1.5rem;
    border-radius: 8px;
    overflow-x: auto;
    margin-bottom: 1.25rem;
}
.catmin-docs-body pre code {
    background: transparent;
    color: inherit;
    padding: 0;
    font-size: 0.9em;
}
.catmin-docs-body blockquote {
    border-left: 4px solid #6c757d;
    padding-left: 1rem;
    color: #6c757d;
    margin: 1rem 0;
}
.catmin-docs-body table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}
.catmin-docs-body table th,
.catmin-docs-body table td {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
}
.catmin-docs-body table thead th { background: #f8f9fa; font-weight: 600; }
.catmin-docs-body hr { margin: 2rem 0; border-color: #dee2e6; }
.catmin-docs-body a { color: #0d6efd; }
.catmin-docs-body img { max-width: 100%; border-radius: 6px; }
</style>
@endpush
@endsection
