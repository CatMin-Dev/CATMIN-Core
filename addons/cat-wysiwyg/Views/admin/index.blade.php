@extends('admin.layouts.catmin')

@section('page_title', 'CAT WYSIWYG')

@section('content')
<x-admin.crud.page-header
    title="CAT WYSIWYG"
    subtitle="Configurer les fonctions de la toolbar, les snippets et les champs editor-enabled."
/>

<div class="catmin-page-body">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('admin.addon.cat_wysiwyg.update') }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-body d-grid gap-4">
            <section>
                <h2 class="h6 mb-2">Fonctions toolbar</h2>
                <p class="text-muted small mb-3">Active ou desactive les actions disponibles dans l'editeur.</p>
                <div class="row g-2">
                    @foreach($allTools as $tool)
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-check border rounded px-3 py-2">
                                <input class="form-check-input" type="checkbox" name="toolbar_tools[]" value="{{ $tool }}" @checked(in_array($tool, $toolbarTools, true))>
                                <span class="form-check-label ms-1">{{ $tool }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </section>

            <section>
                <h2 class="h6 mb-2">Champs actifs</h2>
                <p class="text-muted small mb-2">1 regle par ligne. Exemples: <code>pages.create.content</code>, <code>articles.*.excerpt</code>, <code>*.*.content</code>.</p>
                <textarea class="form-control" name="enabled_fields" rows="8">{{ old('enabled_fields', implode("\n", $enabledFields)) }}</textarea>
            </section>

            <section>
                <h2 class="h6 mb-2">Snippets personnalisables</h2>
                <p class="text-muted small mb-2">JSON d'objets <code>{"label":"...","html":"..."}</code>.</p>
                <textarea class="form-control font-monospace" name="snippets_json" rows="14">{{ old('snippets_json', $snippetsJson) }}</textarea>
            </section>

            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Enregistrer la configuration
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
