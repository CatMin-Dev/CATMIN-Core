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
                @if($hasTool('bold'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="bold" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Gras"><i class="bi bi-type-bold"></i></button>@endif
                @if($hasTool('italic'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="italic" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Italique"><i class="bi bi-type-italic"></i></button>@endif
                @if($hasTool('underline'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="underline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Souligné"><i class="bi bi-type-underline"></i></button>@endif
                @if($hasTool('strike'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="strikeThrough" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Barré"><i class="bi bi-type-strikethrough"></i></button>@endif

                @if($hasTool('align-left') || $hasTool('align-center') || $hasTool('align-right') || $hasTool('align-justify'))
                <div class="vr mx-1"></div>
                @endif

                @if($hasTool('align-left'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyLeft" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Aligner à gauche"><i class="bi bi-text-left"></i></button>@endif
                @if($hasTool('align-center'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyCenter" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Centrer"><i class="bi bi-text-center"></i></button>@endif
                @if($hasTool('align-right'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyRight" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Aligner à droite"><i class="bi bi-text-right"></i></button>@endif
                @if($hasTool('align-justify'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="justifyFull" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Justifier"><i class="bi bi-justify"></i></button>@endif

                @if($hasTool('ul') || $hasTool('ol') || $hasTool('blockquote') || $hasTool('code-block') || $hasTool('h1') || $hasTool('h2') || $hasTool('h3') || $hasTool('h4') || $hasTool('h5') || $hasTool('h6'))
                <div class="vr mx-1"></div>
                @endif

                @if($hasTool('ul'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="insertUnorderedList" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Liste à puces"><i class="bi bi-list-ul"></i></button>@endif
                @if($hasTool('ol'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="insertOrderedList" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Liste numérotée"><i class="bi bi-list-ol"></i></button>@endif
                @if($hasTool('blockquote'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="formatBlock" data-editor-value="blockquote" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Citation"><i class="bi bi-blockquote-left"></i></button>@endif
                @if($hasTool('code-block'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="formatBlock" data-editor-value="pre" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Code"><i class="bi bi-code-square"></i></button>@endif

                @if($hasTool('h1') || $hasTool('h2') || $hasTool('h3') || $hasTool('h4') || $hasTool('h5') || $hasTool('h6'))
                <select class="form-select form-select-sm w-auto" data-editor-cmd="formatBlock" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Titre ou paragraphe">
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
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Couleur du texte" data-editor-action="color-picker" data-editor-color-cmd="foreColor"><i class="bi bi-palette"></i></button>
                    <div class="catmin-editor-color-picker" data-editor-color-picker="foreColor" hidden></div>
                </div>
                @endif
                @if($hasTool('bg-color'))
                <div class="position-relative d-inline-block">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Couleur de fond" data-editor-action="color-picker" data-editor-color-cmd="hiliteColor"><i class="bi bi-paint-bucket"></i></button>
                    <div class="catmin-editor-color-picker" data-editor-color-picker="hiliteColor" hidden></div>
                </div>
                @endif

                @if($hasTool('link'))
                <div class="position-relative">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="link" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Insérer un lien"><i class="bi bi-link-45deg"></i></button>
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
                @if($hasTool('modal-link'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="modal-link" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Lier un bouton à une modal"><i class="bi bi-window-sidebar"></i></button>@endif
                @if($hasTool('bookmarks'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="insert-bookmarks" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Insérer un bloc de signets flottants"><i class="bi bi-bookmarks"></i></button>@endif
                @if($hasTool('clear'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="removeFormat" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Effacer la mise en forme"><i class="bi bi-eraser"></i></button>@endif
                @if($hasTool('undo'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="undo" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Annuler"><i class="bi bi-arrow-counterclockwise"></i></button>@endif
                @if($hasTool('redo'))<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-cmd="redo" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Refaire"><i class="bi bi-arrow-clockwise"></i></button>@endif

                <button type="button" class="btn btn-sm btn-outline-secondary" data-editor-action="media-picker" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Insérer une image"><i class="bi bi-image me-1"></i>Media</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-editor-action="toggle-html" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Basculer en édition HTML"><i class="bi bi-code-slash me-1"></i><span data-editor-html-toggle-label>HTML</span></button>

                @if($hasTool('panel') && $panelEnabled)
                <div class="ms-auto d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-editor-action="toggle-panel" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Afficher la bibliothèque">
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
                    <textarea class="form-control flex-grow-1 border-0 rounded-0 d-none"
                              data-editor-html-mode
                              spellcheck="false"
                              style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, Liberation Mono, monospace; min-height: 22rem;"></textarea>
                </div>
                        $integration = editor_field((string) $scope, (string) $field, ['component' => 'wysiwyg-field']);
                        $mode = (string) ($integration['mode'] ?? 'simple');
                        $enabled = (bool) ($integration['enabled'] ?? false);
                        $mediaAllowed = (bool) ($integration['media_allowed'] ?? false);
                        $panelEnabled = (bool) ($integration['panel_enabled'] ?? false);
                        $snippetItems = (array) ($integration['snippets'] ?? $manager->snippetItems(['scope' => $scope, 'field' => $field]));
                        $blockItems = (array) ($integration['blocks'] ?? $manager->blockItems(['scope' => $scope, 'field' => $field]));

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

                    <div class="p-3 border-bottom" data-editor-pane="snippets" style="max-height: 500px; overflow-y: auto;">
                        <h6 class="small text-uppercase text-muted mb-3">Snippets Bootstrap</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse ($snippetItems as $snippet)
                                <button type="button"
                                        class="btn btn-sm btn-light border catmin-editor-icon-btn"
                                        draggable="true"
                                        data-editor-draggable-item="1"
                                        data-editor-action="insert-html"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="{{ (string) ($snippet['label'] ?? 'Snippet') }}"
                                        data-editor-css="{{ e((string) ($snippet['css'] ?? '')) }}"
                                        data-editor-html="{{ e((string) ($snippet['html'] ?? '')) }}">
                                    @php($snippetIcon = (string) ($snippet['icon'] ?? ''))
                                    @if($snippetIcon !== '' && (str_starts_with($snippetIcon, 'http://') || str_starts_with($snippetIcon, 'https://') || str_starts_with($snippetIcon, '/')))
                                        <img src="{{ $snippetIcon }}" alt="" style="width:1rem;height:1rem;object-fit:contain;">
                                    @else
                                        <i class="bi {{ $snippetIcon !== '' ? $snippetIcon : 'bi-stars' }}"></i>
                                    @endif
                                </button>
                            @empty
                                <p class="small text-muted mb-0">Aucun snippet disponible.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="p-3" data-editor-pane="blocks" hidden style="max-height: 500px; overflow-y: auto;">
                        <h6 class="small text-uppercase text-muted mb-3">Blocs</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse ($blockItems as $block)
                                <button type="button"
                                        class="btn btn-sm btn-light border catmin-editor-icon-btn"
                                        draggable="true"
                                        data-editor-draggable-item="1"
                                        data-editor-action="insert-html"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="{{ (string) ($block['label'] ?? 'Bloc') }}"
                                        data-editor-css="{{ e((string) ($block['css'] ?? '')) }}"
                                        data-editor-html="{{ e((string) ($block['html'] ?? '')) }}">
                                    @php($blockIcon = (string) ($block['icon'] ?? ''))
                                    @if($blockIcon !== '' && (str_starts_with($blockIcon, 'http://') || str_starts_with($blockIcon, 'https://') || str_starts_with($blockIcon, '/')))
                                        <img src="{{ $blockIcon }}" alt="" style="width:1rem;height:1rem;object-fit:contain;">
                                    @else
                                        <i class="bi {{ $blockIcon !== '' ? $blockIcon : 'bi-grid-3x3-gap' }}"></i>
                                    @endif
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
