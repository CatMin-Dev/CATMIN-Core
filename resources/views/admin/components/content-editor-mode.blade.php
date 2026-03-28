@php
    $contentValue = $contentValue ?? '';
    $defaultMode = $defaultMode ?? 'summernote';
    $builderPayload = $builderPayload ?? '';
@endphp

<div class="col-12">
    <label class="form-label fw-semibold">Mode d'edition du contenu</label>
    <div class="d-flex flex-wrap gap-3 mb-2">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="editor_mode_picker" id="editor-mode-summernote" value="summernote">
            <label class="form-check-label" for="editor-mode-summernote">
                Summernote (visuel + HTML)
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="editor_mode_picker" id="editor-mode-builder" value="builder">
            <label class="form-check-label" for="editor-mode-builder">
                Builder (sections stylees)
            </label>
        </div>
    </div>
    <input type="hidden" id="editor_mode" name="editor_mode" value="{{ $defaultMode }}">
    <input type="hidden" id="builder_payload" name="builder_payload" value="{{ $builderPayload }}">
    <div class="form-text">Un seul mode actif a la fois. Summernote inclut le mode code HTML.</div>
</div>

<div class="col-12" id="summernote-wrapper">
    <label class="form-label fw-semibold">Contenu</label>
    <textarea id="content" name="content" class="form-control @error('content') is-invalid @enderror">{{ $contentValue }}</textarea>
    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="col-12 d-none" id="builder-wrapper">
    <label class="form-label fw-semibold">Page Builder</label>
    <div class="card border">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <select id="builder-template" class="form-select form-select-sm" style="max-width: 260px;">
                    <option value="hero">Section Hero</option>
                    <option value="text">Texte</option>
                    <option value="media">Media</option>
                    <option value="cta">Call To Action</option>
                </select>
                <button type="button" id="builder-add" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i>Ajouter une section
                </button>
            </div>
            <div id="builder-blocks" class="vstack gap-2"></div>
            <div class="form-text mt-2">Le builder genere automatiquement du HTML propre dans le champ contenu.</div>
        </div>
    </div>
</div>
