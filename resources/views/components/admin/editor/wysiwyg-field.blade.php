@props([
    'name',
    'id',
    'label' => 'Contenu',
    'value' => '',
    'placeholder' => '',
    'helpText' => null,
    'error' => null,
    'scope' => '',
    'field' => 'content',
])

@php
    $manager = app(\App\Services\Editor\WysiwygManager::class);
    $enabled = $manager->isFieldEnabled((string) $scope, (string) $field);
    $snippetItems = $manager->snippetItems(['scope' => $scope, 'field' => $field]);
    $blockItems = $manager->blockItems(['scope' => $scope, 'field' => $field]);
    $tools = collect($manager->tools());
    $hasTool = fn (string $key): bool => $tools->contains($key);
    $resolvedError = $error ?: $name;
@endphp

<div class="catmin-editor-field" data-catmin-editor-field data-enabled="{{ $enabled ? '1' : '0' }}">
    <label class="form-label fw-semibold" for="{{ $id }}">{{ $label }}</label>

    @if ($enabled)
        <div class="catmin-editor border rounded" data-catmin-editor data-editor-input="{{ $id }}">
            <div class="catmin-editor-toolbar border-bottom p-2 d-flex flex-wrap gap-1" role="toolbar" aria-label="Toolbar editeur">
                @if($hasTool('bold'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="bold"><i class="bi bi-type-bold"></i></button>@endif
                @if($hasTool('italic'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="italic"><i class="bi bi-type-italic"></i></button>@endif
                @if($hasTool('underline'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="underline"><i class="bi bi-type-underline"></i></button>@endif
                @if($hasTool('strike'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="strikeThrough"><i class="bi bi-type-strikethrough"></i></button>@endif

                @if($hasTool('align-left') || $hasTool('align-center') || $hasTool('align-right') || $hasTool('align-justify'))
                <div class="vr mx-1"></div>
                @endif

                @if($hasTool('align-left'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyLeft"><i class="bi bi-text-left"></i></button>@endif
                @if($hasTool('align-center'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyCenter"><i class="bi bi-text-center"></i></button>@endif
                @if($hasTool('align-right'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyRight"><i class="bi bi-text-right"></i></button>@endif
                @if($hasTool('align-justify'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyFull"><i class="bi bi-justify"></i></button>@endif

                @if($hasTool('ul') || $hasTool('ol') || $hasTool('blockquote') || $hasTool('code-block') || $hasTool('h1') || $hasTool('h2') || $hasTool('h3') || $hasTool('h4') || $hasTool('h5') || $hasTool('h6'))
                <div class="vr mx-1"></div>
                @endif

                @if($hasTool('ul'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="insertUnorderedList"><i class="bi bi-list-ul"></i></button>@endif
                @if($hasTool('ol'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="insertOrderedList"><i class="bi bi-list-ol"></i></button>@endif
                @if($hasTool('blockquote'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="formatBlock" data-editor-value="blockquote"><i class="bi bi-blockquote-left"></i></button>@endif
                @if($hasTool('code-block'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="formatBlock" data-editor-value="pre"><i class="bi bi-code-square"></i></button>@endif

                @if($hasTool('h1') || $hasTool('h2') || $hasTool('h3') || $hasTool('h4') || $hasTool('h5') || $hasTool('h6'))
                <select class="form-select form-select-sm w-auto" data-editor-cmd="formatBlock">
                    <option value="p">Paragraphe</option>
                    @if($hasTool('h1'))<option value="h1">Titre H1</option>@endif
                    @if($hasTool('h2'))<option value="h2">Titre H2</option>@endif
                    @if($hasTool('h3'))<option value="h3">Titre H3</option>@endif
                    @if($hasTool('h4'))<option value="h4">Titre H4</option>@endif
                    @if($hasTool('h5'))<option value="h5">Titre H5</option>@endif
                    @if($hasTool('h6'))<option value="h6">Titre H6</option>@endif
                </select>
                @endif

                @if($hasTool('text-color'))
                <div class="position-relative d-inline-block">
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Couleur texte" data-editor-action="color-picker" data-editor-color-cmd="foreColor"><i class="bi bi-palette"></i></button>
                    <div class="catmin-editor-color-picker" data-editor-color-picker="foreColor" hidden></div>
                </div>
                @endif
                @if($hasTool('bg-color'))
                <div class="position-relative d-inline-block">
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Couleur fond" data-editor-action="color-picker" data-editor-color-cmd="hiliteColor"><i class="bi bi-paint-bucket"></i></button>
                    <div class="catmin-editor-color-picker" data-editor-color-picker="hiliteColor" hidden></div>
                </div>
                @endif

                @if($hasTool('link'))
                <div class="position-relative">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="link" title="Insérer un lien"><i class="bi bi-link-45deg"></i></button>
                    <div class="catmin-editor-link-popup card shadow border p-2 gap-2" data-editor-link-popup hidden>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" placeholder="https://..." data-editor-link-input autocomplete="off">
                            <button type="button" class="btn btn-primary" data-editor-link-apply title="Appliquer"><i class="bi bi-check-lg"></i></button>
                            <button type="button" class="btn btn-outline-secondary" data-editor-link-cancel title="Annuler"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <small class="text-muted">Sélectionnez du texte puis saisissez l'URL.</small>
                    </div>
                </div>
                @endif
                @if($hasTool('clear'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="removeFormat"><i class="bi bi-eraser"></i></button>@endif
                @if($hasTool('undo'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="undo"><i class="bi bi-arrow-counterclockwise"></i></button>@endif
                @if($hasTool('redo'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="redo"><i class="bi bi-arrow-clockwise"></i></button>@endif

                <button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="media-picker" title="Insérer une image"><i class="bi bi-image me-1"></i>Media</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-editor-action="toggle-preview" title="Aperçu live"><i class="bi bi-eye me-1"></i>Aperçu</button>

                @if($hasTool('panel'))
                <div class="ms-auto d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-editor-action="toggle-panel">
                        <i class="bi bi-layout-sidebar"></i> Snippets / Blocs
                    </button>
                </div>
                @endif
            </div>

            <div class="catmin-editor-body position-relative d-flex">
                <div class="catmin-editor-content flex-grow-1 d-flex flex-column">
                    <div class="catmin-editor-canvas flex-grow-1 p-3 overflow-auto"
                         contenteditable="true"
                         data-editor-canvas
                         data-editor-placeholder="{{ $placeholder }}">{!! old($name, $value) !!}</div>
                </div>

                <div class="catmin-editor-preview border-start flex-shrink-0" data-editor-preview-pane hidden>
                    <div class="p-3 border-bottom bg-light">
                        <small class="text-muted">Aperçu live</small>
                    </div>
                    <div class="p-3 overflow-auto" data-editor-preview-canvas></div>
                </div>

                <aside class="catmin-editor-panel border-start" data-editor-panel @if(!$hasTool('panel')) hidden @endif>
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Bibliothèque</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="toggle-panel">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-3 border-bottom">
                        <div class="btn-group w-100" role="group" aria-label="Volets bibliothèque editor">
                            <button type="button" class="btn btn-sm btn-outline-primary active" data-editor-tab="snippets">Snippets</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-editor-tab="blocks">Blocs</button>
                        </div>
                    </div>

                    <div class="p-3 border-bottom" data-editor-pane="snippets">
                        <h6 class="small text-uppercase text-muted mb-3">Snippets Bootstrap</h6>
                        <div class="d-grid gap-2">
                            @forelse ($snippetItems as $snippet)
                                <button type="button"
                                        class="btn btn-sm btn-light text-start border"
                                        data-editor-action="insert-html"
                                        data-editor-html="{{ e((string) ($snippet['html'] ?? '')) }}">
                                    {{ (string) ($snippet['label'] ?? 'Snippet') }}
                                </button>
                            @empty
                                <p class="small text-muted mb-0">Aucun snippet disponible.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="p-3" data-editor-pane="blocks" hidden>
                        <h6 class="small text-uppercase text-muted mb-3">Blocs</h6>
                        <div class="d-grid gap-2">
                            @forelse ($blockItems as $block)
                                <button type="button"
                                        class="btn btn-sm btn-light text-start border"
                                        data-editor-action="insert-html"
                                        data-editor-html="{{ e((string) ($block['html'] ?? '')) }}">
                                    {{ (string) ($block['label'] ?? 'Bloc') }}
                                </button>
                            @empty
                                <p class="small text-muted mb-0">Aucun bloc disponible.</p>
                            @endforelse
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <textarea id="{{ $id }}"
                  name="{{ $name }}"
                  class="form-control d-none @error($resolvedError) is-invalid @enderror"
                  data-editor-source
                  aria-hidden="true">{{ old($name, $value) }}</textarea>
    @else
        <textarea id="{{ $id }}"
                  name="{{ $name }}"
                  rows="12"
                  class="form-control @error($resolvedError) is-invalid @enderror"
                  placeholder="{{ $placeholder }}">{{ old($name, $value) }}</textarea>
    @endif

    @error($resolvedError)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

    @if ($helpText)
        <div class="form-text">{{ $helpText }}</div>
    @endif
</div>
