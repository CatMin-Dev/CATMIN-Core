@extends('admin.layouts.catmin')

@section('page_title', 'CAT WYSIWYG')

@section('content')
@php
    $snippetSeed = old('snippets_json');
    $snippetItems = is_string($snippetSeed) ? json_decode($snippetSeed, true) : $snippets;
    $snippetItems = is_array($snippetItems) ? $snippetItems : [];

    $blockSeed = old('blocks_json');
    $blockItems = is_string($blockSeed) ? json_decode($blockSeed, true) : $blocks;
    $blockItems = is_array($blockItems) ? $blockItems : [];

    $iconGroups = [
        'Colonnes / Layout' => ['bi-columns-gap', 'bi-layout-split', 'bi-grid-3x2-gap', 'bi-grid-3x3-gap', 'bi-grid-1x2', 'bi-grid-1x2-fill'],
        'Composants UI' => ['bi-card-text', 'bi-card-heading', 'bi-layout-text-sidebar', 'bi-list-ul', 'bi-list-check', 'bi-table'],
        'Actions / Liens' => ['bi-cursor', 'bi-link-45deg', 'bi-box-arrow-up-right', 'bi-hand-index-thumb', 'bi-lightning-charge'],
        'Mises en avant' => ['bi-stars', 'bi-megaphone', 'bi-chat-square-text', 'bi-quote', 'bi-bookmark-star'],
        'Statuts / Alertes' => ['bi-info-circle', 'bi-check-circle', 'bi-exclamation-triangle', 'bi-x-circle', 'bi-patch-check'],
        'Mise en page' => ['bi-text-left', 'bi-text-center', 'bi-text-right', 'bi-bounding-box', 'bi-square'],
    ];
@endphp

<x-admin.crud.page-header
    title="CAT WYSIWYG"
    subtitle="Configuration avancée de la toolbar, des champs et de la bibliothèque Snippets/Blocs."
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

        <div class="card-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-tab-toolbar" role="tab">Toolbar</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-tab-fields" role="tab">Champs actifs</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-tab-snippets" role="tab">Snippets</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-tab-blocks" role="tab">Blocs</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="wysiwyg-tab-toolbar" role="tabpanel">
                    <h2 class="h6 mb-2">Fonctions toolbar</h2>
                    <p class="text-muted small mb-3">Active ou désactive les actions disponibles dans l’éditeur.</p>
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
                </div>

                <div class="tab-pane fade" id="wysiwyg-tab-fields" role="tabpanel">
                    <h2 class="h6 mb-2">Champs actifs</h2>
                    <p class="text-muted small mb-2">1 règle par ligne. Ex: <code>pages.create.content</code>, <code>articles.*.excerpt</code>, <code>*.*.content</code>.</p>
                    <textarea class="form-control" name="enabled_fields" rows="10">{{ old('enabled_fields', implode("\n", $enabledFields)) }}</textarea>
                </div>

                <div class="tab-pane fade" id="wysiwyg-tab-snippets" role="tabpanel">
                    <ul class="nav nav-pills nav-sm mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-snippets-existing" role="tab">Codes existants</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-snippets-add" role="tab">Ajouter</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="wysiwyg-snippets-existing" role="tabpanel">
                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 18%">Label</th>
                                            <th style="width: 18%">Icône</th>
                                            <th style="width: 24%">CSS dédié</th>
                                            <th>HTML</th>
                                            <th style="width: 1%"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-snippet-list>
                                        @forelse($snippetItems as $item)
                                            <tr data-editor-item>
                                                <td><input type="text" class="form-control form-control-sm" data-item-label name="snippets_rows[{{ $loop->index }}][label]" value="{{ (string)($item['label'] ?? '') }}"></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-white" data-item-icon-preview data-bs-toggle="tooltip" title="{{ (string)($item['label'] ?? 'Icone') }}">
                                                            <i class="bi {{ (string)($item['icon'] ?? 'bi-stars') }}"></i>
                                                        </span>
                                                        <input type="text" class="form-control" data-item-icon name="snippets_rows[{{ $loop->index }}][icon]" value="{{ (string)($item['icon'] ?? '') }}" placeholder="bi-stars">
                                                        <select class="form-select" data-item-icon-select>
                                                            <option value="">Type / icone</option>
                                                            @foreach($iconGroups as $groupLabel => $icons)
                                                                <optgroup label="{{ $groupLabel }}">
                                                                    @foreach($icons as $icon)
                                                                        <option value="{{ $icon }}">{{ $icon }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </td>
                                                <td><textarea class="form-control form-control-sm font-monospace" rows="3" data-item-css name="snippets_rows[{{ $loop->index }}][css]" placeholder="background:#f8f9fa; padding:1rem;">{{ (string)($item['css'] ?? '') }}</textarea></td>
                                                <td><textarea class="form-control form-control-sm font-monospace" rows="3" data-item-html name="snippets_rows[{{ $loop->index }}][html]">{{ (string)($item['html'] ?? '') }}</textarea></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger" data-item-remove><i class="bi bi-trash"></i></button></td>
                                            </tr>
                                        @empty
                                            <tr data-empty-row><td colspan="5" class="text-muted small">Aucun snippet. Ajoutez-en un.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="wysiwyg-snippets-add" role="tabpanel">
                            <div class="card border">
                                <div class="card-body d-grid gap-2">
                                    <input type="text" class="form-control form-control-sm" placeholder="Label snippet" data-snippet-add-label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white" data-add-icon-preview>
                                            <i class="bi bi-stars"></i>
                                        </span>
                                        <input type="text" class="form-control" placeholder="Icône bootstrap (ex: bi-stars)" data-snippet-add-icon>
                                        <select class="form-select" data-snippet-add-icon-select>
                                            <option value="">Type / icone</option>
                                            @foreach($iconGroups as $groupLabel => $icons)
                                                <optgroup label="{{ $groupLabel }}">
                                                    @foreach($icons as $icon)
                                                        <option value="{{ $icon }}">{{ $icon }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <textarea class="form-control form-control-sm font-monospace" rows="3" placeholder="CSS dédié du wrapper (optionnel)" data-snippet-add-css></textarea>
                                    <textarea class="form-control form-control-sm font-monospace" rows="5" placeholder="HTML snippet" data-snippet-add-html></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-snippet-add>
                                        <i class="bi bi-plus-lg me-1"></i>Ajouter à la liste snippets
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="wysiwyg-tab-blocks" role="tabpanel">
                    <ul class="nav nav-pills nav-sm mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-blocks-existing" role="tab">Codes existants</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#wysiwyg-blocks-add" role="tab">Ajouter</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="wysiwyg-blocks-existing" role="tabpanel">
                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 18%">Label</th>
                                            <th style="width: 18%">Icône</th>
                                            <th style="width: 24%">CSS dédié</th>
                                            <th>HTML</th>
                                            <th style="width: 1%"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-block-list>
                                        @forelse($blockItems as $item)
                                            <tr data-editor-item>
                                                <td><input type="text" class="form-control form-control-sm" data-item-label name="blocks_rows[{{ $loop->index }}][label]" value="{{ (string)($item['label'] ?? '') }}"></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-white" data-item-icon-preview data-bs-toggle="tooltip" title="{{ (string)($item['label'] ?? 'Icone') }}">
                                                            <i class="bi {{ (string)($item['icon'] ?? 'bi-grid-3x3-gap') }}"></i>
                                                        </span>
                                                        <input type="text" class="form-control" data-item-icon name="blocks_rows[{{ $loop->index }}][icon]" value="{{ (string)($item['icon'] ?? '') }}" placeholder="bi-grid-3x3-gap">
                                                        <select class="form-select" data-item-icon-select>
                                                            <option value="">Type / icone</option>
                                                            @foreach($iconGroups as $groupLabel => $icons)
                                                                <optgroup label="{{ $groupLabel }}">
                                                                    @foreach($icons as $icon)
                                                                        <option value="{{ $icon }}">{{ $icon }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </td>
                                                <td><textarea class="form-control form-control-sm font-monospace" rows="3" data-item-css name="blocks_rows[{{ $loop->index }}][css]" placeholder="background:#f8f9fa; padding:1rem;">{{ (string)($item['css'] ?? '') }}</textarea></td>
                                                <td><textarea class="form-control form-control-sm font-monospace" rows="3" data-item-html name="blocks_rows[{{ $loop->index }}][html]">{{ (string)($item['html'] ?? '') }}</textarea></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger" data-item-remove><i class="bi bi-trash"></i></button></td>
                                            </tr>
                                        @empty
                                            <tr data-empty-row><td colspan="5" class="text-muted small">Aucun bloc. Ajoutez-en un.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="wysiwyg-blocks-add" role="tabpanel">
                            <div class="card border">
                                <div class="card-body d-grid gap-2">
                                    <input type="text" class="form-control form-control-sm" placeholder="Label bloc" data-block-add-label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white" data-add-icon-preview>
                                            <i class="bi bi-grid-3x3-gap"></i>
                                        </span>
                                        <input type="text" class="form-control" placeholder="Icône bootstrap (ex: bi-grid-3x3-gap)" data-block-add-icon>
                                        <select class="form-select" data-block-add-icon-select>
                                            <option value="">Type / icone</option>
                                            @foreach($iconGroups as $groupLabel => $icons)
                                                <optgroup label="{{ $groupLabel }}">
                                                    @foreach($icons as $icon)
                                                        <option value="{{ $icon }}">{{ $icon }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <textarea class="form-control form-control-sm font-monospace" rows="3" placeholder="CSS dédié du wrapper (optionnel)" data-block-add-css></textarea>
                                    <textarea class="form-control form-control-sm font-monospace" rows="5" placeholder="HTML bloc" data-block-add-html></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-block-add>
                                        <i class="bi bi-plus-lg me-1"></i>Ajouter à la liste blocs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="snippets_json" data-snippets-json value="{{ e(json_encode($snippetItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}">
            <input type="hidden" name="blocks_json" data-blocks-json value="{{ e(json_encode($blockItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}">

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Enregistrer la configuration
                </button>
            </div>
        </div>
    </form>
</div>

<template id="editor-item-template">
    <tr data-editor-item>
        <td><input type="text" class="form-control form-control-sm" data-item-label></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white" data-item-icon-preview><i class="bi bi-stars"></i></span>
                <input type="text" class="form-control" data-item-icon>
                <select class="form-select" data-item-icon-select>
                    <option value="">Type / icone</option>
                    @foreach($iconGroups as $groupLabel => $icons)
                        <optgroup label="{{ $groupLabel }}">
                            @foreach($icons as $icon)
                                <option value="{{ $icon }}">{{ $icon }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </td>
        <td><textarea class="form-control form-control-sm font-monospace" rows="3" data-item-css></textarea></td>
        <td><textarea class="form-control form-control-sm font-monospace" rows="3" data-item-html></textarea></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-item-remove><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action*="cat-wysiwyg"]');
    if (!form) return;

    const tpl = document.getElementById('editor-item-template');
    const snippetList = form.querySelector('[data-snippet-list]');
    const blockList = form.querySelector('[data-block-list]');
    const snippetsJsonInput = form.querySelector('[data-snippets-json]');
    const blocksJsonInput = form.querySelector('[data-blocks-json]');

    const addSnippetBtn = form.querySelector('[data-snippet-add]');
    const addBlockBtn = form.querySelector('[data-block-add]');
    const addSnippetLabel = form.querySelector('[data-snippet-add-label]');
    const addSnippetIcon = form.querySelector('[data-snippet-add-icon]');
    const addSnippetIconSelect = form.querySelector('[data-snippet-add-icon-select]');
    const addSnippetCss = form.querySelector('[data-snippet-add-css]');
    const addSnippetHtml = form.querySelector('[data-snippet-add-html]');
    const addBlockLabel = form.querySelector('[data-block-add-label]');
    const addBlockIcon = form.querySelector('[data-block-add-icon]');
    const addBlockIconSelect = form.querySelector('[data-block-add-icon-select]');
    const addBlockCss = form.querySelector('[data-block-add-css]');
    const addBlockHtml = form.querySelector('[data-block-add-html]');

    const updateHiddenInputs = () => {
        snippetsJsonInput.value = JSON.stringify(serializeList(snippetList));
        blocksJsonInput.value = JSON.stringify(serializeList(blockList));
    };

    const refreshTooltips = (scope = document) => {
        if (!window.bootstrap?.Tooltip) return;
        scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            window.bootstrap.Tooltip.getOrCreateInstance(el);
        });
    };

    const syncRowIconPreview = (row) => {
        const iconInput = row.querySelector('[data-item-icon]');
        const previewWrap = row.querySelector('[data-item-icon-preview]');
        const labelInput = row.querySelector('[data-item-label]');
        const iconValue = (iconInput?.value || '').trim();
        const isUrlIcon = /^(https?:\/\/|\/)/i.test(iconValue);
        if (previewWrap) {
            previewWrap.innerHTML = isUrlIcon
                ? `<img src="${iconValue}" alt="" style="width:1rem;height:1rem;object-fit:contain;">`
                : `<i class="bi ${iconValue || 'bi-stars'}"></i>`;
            previewWrap.setAttribute('data-bs-toggle', 'tooltip');
            previewWrap.setAttribute('title', (labelInput?.value || 'Icone').trim() || 'Icone');
        }
        refreshTooltips(row);
    };

    const bindIconSelectors = (scope) => {
        scope.querySelectorAll('[data-editor-item]').forEach((row) => {
            const iconInput = row.querySelector('[data-item-icon]');
            const iconSelect = row.querySelector('[data-item-icon-select]');
            const labelInput = row.querySelector('[data-item-label]');

            iconSelect?.addEventListener('change', () => {
                if (iconInput) {
                    iconInput.value = iconSelect.value;
                }
                syncRowIconPreview(row);
            });

            iconInput?.addEventListener('input', () => syncRowIconPreview(row));
            labelInput?.addEventListener('input', () => syncRowIconPreview(row));
            syncRowIconPreview(row);
        });
    };

    const bindAddIconSelect = (selectEl, inputEl) => {
        selectEl?.addEventListener('change', () => {
            if (inputEl) {
                inputEl.value = selectEl.value;
                const preview = selectEl.closest('.input-group')?.querySelector('[data-add-icon-preview]');
                if (preview) {
                    preview.innerHTML = /^(https?:\/\/|\/)/i.test(inputEl.value || '')
                        ? `<img src="${inputEl.value}" alt="" style="width:1rem;height:1rem;object-fit:contain;">`
                        : `<i class="bi ${inputEl.value || 'bi-stars'}"></i>`;
                }
            }
        });

        inputEl?.addEventListener('input', () => {
            const preview = inputEl.closest('.input-group')?.querySelector('[data-add-icon-preview]');
            if (preview) {
                const iconValue = (inputEl.value || '').trim();
                preview.innerHTML = /^(https?:\/\/|\/)/i.test(iconValue)
                    ? `<img src="${iconValue}" alt="" style="width:1rem;height:1rem;object-fit:contain;">`
                    : `<i class="bi ${iconValue || 'bi-stars'}"></i>`;
            }
        });
    };

    const removeEmpty = (list, emptyText) => {
        list.querySelector('[data-empty-row]')?.remove();
        if (!list.querySelector('[data-editor-item]')) {
            const tr = document.createElement('tr');
            tr.setAttribute('data-empty-row', '1');
            tr.innerHTML = `<td colspan="5" class="text-muted small">${emptyText}</td>`;
            list.appendChild(tr);
        }
    };

    const wireRemoveButtons = (list, emptyText) => {
        list.querySelectorAll('[data-item-remove]').forEach((btn) => {
            btn.onclick = () => {
                btn.closest('[data-editor-item]')?.remove();
                removeEmpty(list, emptyText);
                updateHiddenInputs();
            };
        });
    };

    const addItem = (list, emptyText, item) => {
        list.querySelector('[data-empty-row]')?.remove();
        const row = tpl.content.firstElementChild.cloneNode(true);
        const idx = list.querySelectorAll('[data-editor-item]').length;
        const namespace = list === snippetList ? 'snippets_rows' : 'blocks_rows';
        row.querySelector('[data-item-label]')?.setAttribute('name', `${namespace}[${idx}][label]`);
        row.querySelector('[data-item-icon]')?.setAttribute('name', `${namespace}[${idx}][icon]`);
        row.querySelector('[data-item-css]')?.setAttribute('name', `${namespace}[${idx}][css]`);
        row.querySelector('[data-item-html]')?.setAttribute('name', `${namespace}[${idx}][html]`);
        row.querySelector('[data-item-label]').value = item.label || '';
        row.querySelector('[data-item-icon]').value = item.icon || '';
        row.querySelector('[data-item-css]').value = item.css || '';
        row.querySelector('[data-item-html]').value = item.html || '';
        list.appendChild(row);
        wireRemoveButtons(list, emptyText);
        bindIconSelectors(row.closest('tbody'));
        updateHiddenInputs();
    };

    const serializeList = (list) => {
        const rows = Array.from(list.querySelectorAll('[data-editor-item]'));
        return rows.map((row) => ({
            label: (row.querySelector('[data-item-label]')?.value || '').trim(),
            icon: (row.querySelector('[data-item-icon]')?.value || '').trim(),
            css: (row.querySelector('[data-item-css]')?.value || '').trim(),
            html: (row.querySelector('[data-item-html]')?.value || '').trim(),
        })).filter((item) => item.label && item.html);
    };

    addSnippetBtn?.addEventListener('click', () => {
        const label = (addSnippetLabel?.value || '').trim();
        const icon = (addSnippetIcon?.value || '').trim();
        const css = (addSnippetCss?.value || '').trim();
        const html = (addSnippetHtml?.value || '').trim();
        if (!label || !html) return;
        addItem(snippetList, 'Aucun snippet. Ajoutez-en un.', { label, icon, css, html });
        addSnippetLabel.value = '';
        addSnippetIcon.value = '';
        addSnippetCss.value = '';
        addSnippetHtml.value = '';
        updateHiddenInputs();
    });

    addBlockBtn?.addEventListener('click', () => {
        const label = (addBlockLabel?.value || '').trim();
        const icon = (addBlockIcon?.value || '').trim();
        const css = (addBlockCss?.value || '').trim();
        const html = (addBlockHtml?.value || '').trim();
        if (!label || !html) return;
        addItem(blockList, 'Aucun bloc. Ajoutez-en un.', { label, icon, css, html });
        addBlockLabel.value = '';
        addBlockIcon.value = '';
        addBlockCss.value = '';
        addBlockHtml.value = '';
        updateHiddenInputs();
    });

    wireRemoveButtons(snippetList, 'Aucun snippet. Ajoutez-en un.');
    wireRemoveButtons(blockList, 'Aucun bloc. Ajoutez-en un.');
    bindIconSelectors(form);
    bindAddIconSelect(addSnippetIconSelect, addSnippetIcon);
    bindAddIconSelect(addBlockIconSelect, addBlockIcon);
    refreshTooltips(form);

    snippetList?.addEventListener('input', updateHiddenInputs);
    blockList?.addEventListener('input', updateHiddenInputs);
    updateHiddenInputs();

    form.addEventListener('submit', () => {
        updateHiddenInputs();
    });
});
</script>
@endsection
