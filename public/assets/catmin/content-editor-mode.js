(function () {
    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function decodeBase64Unicode(input) {
        try {
            return decodeURIComponent(escape(window.atob(input)));
        } catch (_e) {
            return '';
        }
    }

    function encodeBase64Unicode(input) {
        try {
            return window.btoa(unescape(encodeURIComponent(input)));
        } catch (_e) {
            return '';
        }
    }

    function initContentEditorMode(config) {
        if (!config || !window.jQuery) {
            return;
        }

        var form = document.getElementById(config.formId || 'content-form');
        if (!form) {
            return;
        }

        var $ = window.jQuery;
        var $content = $('#content');
        var $summernoteWrapper = $('#summernote-wrapper');
        var $builderWrapper = $('#builder-wrapper');
        var $modeHidden = $('#editor_mode');
        var $builderPayload = $('#builder_payload');
        var $pickerSummernote = $('#editor-mode-summernote');
        var $pickerBuilder = $('#editor-mode-builder');
        var $builderBlocks = $('#builder-blocks');
        var $builderTemplate = $('#builder-template');

        var BUILDER_MARKER = /<!--CATMIN_BUILDER:([A-Za-z0-9+/=]+)-->/;

        var snippetTemplates = [
            {
                label: 'Hero simple',
                html: '<div class="p-4 rounded border bg-light"><h2>Votre titre</h2><p>Votre texte introductif.</p></div>'
            },
            {
                label: 'Bloc deux colonnes',
                html: '<div class="row g-3"><div class="col-12 col-md-6"><p>Colonne gauche</p></div><div class="col-12 col-md-6"><p>Colonne droite</p></div></div>'
            },
            {
                label: 'CTA',
                html: '<div class="p-4 rounded border bg-dark text-light"><h3>Call to action</h3><p>Texte d\'accroche.</p><p><a href="#">Lien d\'action</a></p></div>'
            }
        ];

        var stylePresets = {
            default: 'p-4 border rounded bg-white',
            muted: 'p-4 border rounded bg-light',
            accent: 'p-4 border rounded bg-warning-subtle',
            dark: 'p-4 border rounded bg-dark text-light'
        };

        var state = {
            mode: ($modeHidden.val() || 'summernote') === 'builder' ? 'builder' : 'summernote',
            blocks: []
        };

        function sanitizeContentForSummernote(rawHtml) {
            if (!rawHtml) {
                return '';
            }
            return String(rawHtml).replace(BUILDER_MARKER, '').trim();
        }

        function tryLoadBlocksFromMarker(rawHtml) {
            var matches = String(rawHtml || '').match(BUILDER_MARKER);
            if (!matches || !matches[1]) {
                return null;
            }

            var json = decodeBase64Unicode(matches[1]);
            if (!json) {
                return null;
            }

            try {
                var parsed = JSON.parse(json);
                return Array.isArray(parsed) ? parsed : null;
            } catch (_e) {
                return null;
            }
        }

        function getDefaultBlock(type) {
            return {
                type: type || 'text',
                title: '',
                text: '',
                media_url: '',
                button_label: '',
                button_url: '',
                style: 'default',
                align: 'start'
            };
        }

        function renderBlocksEditor() {
            if (state.blocks.length === 0) {
                $builderBlocks.html('<div class="text-muted small">Aucune section. Ajoutez-en une pour commencer.</div>');
                return;
            }

            var html = state.blocks.map(function (block, index) {
                return [
                    '<div class="border rounded p-3" data-index="' + index + '">',
                    '  <div class="d-flex justify-content-between align-items-center mb-2">',
                    '    <strong>Section #' + (index + 1) + '</strong>',
                    '    <div class="btn-group btn-group-sm">',
                    '      <button type="button" class="btn btn-outline-secondary js-block-up">↑</button>',
                    '      <button type="button" class="btn btn-outline-secondary js-block-down">↓</button>',
                    '      <button type="button" class="btn btn-outline-danger js-block-delete">Supprimer</button>',
                    '    </div>',
                    '  </div>',
                    '  <div class="row g-2">',
                    '    <div class="col-12 col-lg-4">',
                    '      <label class="form-label form-label-sm mb-1">Type</label>',
                    '      <select class="form-select form-select-sm js-block-field" data-field="type">',
                    '        <option value="hero" ' + (block.type === 'hero' ? 'selected' : '') + '>Hero</option>',
                    '        <option value="text" ' + (block.type === 'text' ? 'selected' : '') + '>Texte</option>',
                    '        <option value="media" ' + (block.type === 'media' ? 'selected' : '') + '>Media</option>',
                    '        <option value="cta" ' + (block.type === 'cta' ? 'selected' : '') + '>CTA</option>',
                    '      </select>',
                    '    </div>',
                    '    <div class="col-12 col-lg-4">',
                    '      <label class="form-label form-label-sm mb-1">Style</label>',
                    '      <select class="form-select form-select-sm js-block-field" data-field="style">',
                    '        <option value="default" ' + (block.style === 'default' ? 'selected' : '') + '>Default</option>',
                    '        <option value="muted" ' + (block.style === 'muted' ? 'selected' : '') + '>Muted</option>',
                    '        <option value="accent" ' + (block.style === 'accent' ? 'selected' : '') + '>Accent</option>',
                    '        <option value="dark" ' + (block.style === 'dark' ? 'selected' : '') + '>Dark</option>',
                    '      </select>',
                    '    </div>',
                    '    <div class="col-12 col-lg-4">',
                    '      <label class="form-label form-label-sm mb-1">Alignement</label>',
                    '      <select class="form-select form-select-sm js-block-field" data-field="align">',
                    '        <option value="start" ' + (block.align === 'start' ? 'selected' : '') + '>Gauche</option>',
                    '        <option value="center" ' + (block.align === 'center' ? 'selected' : '') + '>Centre</option>',
                    '        <option value="end" ' + (block.align === 'end' ? 'selected' : '') + '>Droite</option>',
                    '      </select>',
                    '    </div>',
                    '    <div class="col-12">',
                    '      <label class="form-label form-label-sm mb-1">Titre</label>',
                    '      <input type="text" class="form-control form-control-sm js-block-field" data-field="title" value="' + escapeHtml(block.title) + '">',
                    '    </div>',
                    '    <div class="col-12">',
                    '      <label class="form-label form-label-sm mb-1">Texte</label>',
                    '      <textarea rows="3" class="form-control form-control-sm js-block-field" data-field="text">' + escapeHtml(block.text) + '</textarea>',
                    '    </div>',
                    '    <div class="col-12 col-lg-6">',
                    '      <label class="form-label form-label-sm mb-1">URL media (optionnel)</label>',
                    '      <input type="text" class="form-control form-control-sm js-block-field" data-field="media_url" value="' + escapeHtml(block.media_url) + '">',
                    '    </div>',
                    '    <div class="col-12 col-lg-3">',
                    '      <label class="form-label form-label-sm mb-1">Texte bouton</label>',
                    '      <input type="text" class="form-control form-control-sm js-block-field" data-field="button_label" value="' + escapeHtml(block.button_label) + '">',
                    '    </div>',
                    '    <div class="col-12 col-lg-3">',
                    '      <label class="form-label form-label-sm mb-1">URL bouton</label>',
                    '      <input type="text" class="form-control form-control-sm js-block-field" data-field="button_url" value="' + escapeHtml(block.button_url) + '">',
                    '    </div>',
                    '  </div>',
                    '</div>'
                ].join('');
            }).join('');

            $builderBlocks.html(html);
        }

        function renderBuilderHtml() {
            return state.blocks.map(function (block) {
                var shellClass = stylePresets[block.style] || stylePresets.default;
                var alignClass = block.align === 'center' ? 'text-center' : (block.align === 'end' ? 'text-end' : 'text-start');
                var title = block.title ? '<h2>' + escapeHtml(block.title) + '</h2>' : '';
                var text = block.text ? '<p>' + escapeHtml(block.text).replace(/\n/g, '<br>') + '</p>' : '';
                var media = block.media_url ? '<p><img src="' + escapeHtml(block.media_url) + '" alt=""/></p>' : '';
                var button = (block.button_label && block.button_url)
                    ? '<p><a href="' + escapeHtml(block.button_url) + '">' + escapeHtml(block.button_label) + '</a></p>'
                    : '';

                return '<div class="' + shellClass + ' ' + alignClass + '">' + title + text + media + button + '</div>';
            }).join('\n');
        }

        function syncEditorModeUI() {
            var isBuilder = state.mode === 'builder';
            $modeHidden.val(state.mode);
            $pickerSummernote.prop('checked', !isBuilder);
            $pickerBuilder.prop('checked', isBuilder);
            $summernoteWrapper.toggleClass('d-none', isBuilder);
            $builderWrapper.toggleClass('d-none', !isBuilder);
        }

        var defaultSnippetMap = {
            hero: snippetTemplates[0].html,
            text: '<p>Nouveau contenu...</p>',
            media: '<p><img src="https://via.placeholder.com/1200x500" alt=""/></p>',
            cta: snippetTemplates[2].html
        };

        var initialRaw = $content.val() || '';
        var payloadRaw = String($builderPayload.val() || '').trim();

        if (payloadRaw !== '') {
            try {
                var parsedPayload = JSON.parse(payloadRaw);
                if (Array.isArray(parsedPayload)) {
                    state.blocks = parsedPayload;
                    state.mode = 'builder';
                }
            } catch (_e) {
                // noop
            }
        }

        if (state.blocks.length === 0) {
            var blocksFromContent = tryLoadBlocksFromMarker(initialRaw);
            if (blocksFromContent && blocksFromContent.length > 0) {
                state.blocks = blocksFromContent;
                state.mode = 'builder';
            }
        }

        $content.val(sanitizeContentForSummernote(initialRaw));

        if ($.fn && $.fn.summernote && $content.length > 0) {
            $content.summernote({
                lang: 'fr-FR',
                height: 320,
                toolbar: [
                    [ 'style', [ 'style' ] ],
                    [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear' ] ],
                    [ 'fontsize', [ 'fontsize' ] ],
                    [ 'color', [ 'color' ] ],
                    [ 'para', [ 'ul', 'ol', 'paragraph' ] ],
                    [ 'table', [ 'table' ] ],
                    [ 'insert', [ 'link', 'picture', 'video' ] ],
                    [ 'view', [ 'fullscreen', 'codeview', 'help' ] ]
                ]
            });
        }

        if (state.blocks.length === 0) {
            state.blocks = [getDefaultBlock('hero')];
        }

        renderBlocksEditor();
        syncEditorModeUI();

        $pickerSummernote.on('change', function () {
            state.mode = 'summernote';
            syncEditorModeUI();
        });

        $pickerBuilder.on('change', function () {
            state.mode = 'builder';
            syncEditorModeUI();
        });

        $('#builder-add').on('click', function () {
            var type = $builderTemplate.val();
            var next = getDefaultBlock(type);
            if (defaultSnippetMap[type]) {
                next.text = defaultSnippetMap[type].replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            }
            state.blocks.push(next);
            renderBlocksEditor();
        });

        $builderBlocks.on('input change', '.js-block-field', function () {
            var $field = $(this);
            var fieldName = $field.data('field');
            var index = Number($field.closest('[data-index]').data('index'));

            if (!Number.isFinite(index) || !state.blocks[index]) {
                return;
            }

            state.blocks[index][fieldName] = $field.val();
        });

        $builderBlocks.on('click', '.js-block-delete', function () {
            var index = Number($(this).closest('[data-index]').data('index'));
            if (!Number.isFinite(index)) {
                return;
            }
            state.blocks.splice(index, 1);
            if (state.blocks.length === 0) {
                state.blocks.push(getDefaultBlock('text'));
            }
            renderBlocksEditor();
        });

        $builderBlocks.on('click', '.js-block-up', function () {
            var index = Number($(this).closest('[data-index]').data('index'));
            if (!Number.isFinite(index) || index <= 0) {
                return;
            }
            var swap = state.blocks[index - 1];
            state.blocks[index - 1] = state.blocks[index];
            state.blocks[index] = swap;
            renderBlocksEditor();
        });

        $builderBlocks.on('click', '.js-block-down', function () {
            var index = Number($(this).closest('[data-index]').data('index'));
            if (!Number.isFinite(index) || index >= state.blocks.length - 1) {
                return;
            }
            var swap = state.blocks[index + 1];
            state.blocks[index + 1] = state.blocks[index];
            state.blocks[index] = swap;
            renderBlocksEditor();
        });

        form.addEventListener('submit', function () {
            if (state.mode === 'builder') {
                var payload = JSON.stringify(state.blocks);
                var encoded = encodeBase64Unicode(payload);
                var html = renderBuilderHtml();
                var merged = (encoded ? '<!--CATMIN_BUILDER:' + encoded + '-->\n' : '') + html;

                $builderPayload.val(payload);
                if ($.fn && $.fn.summernote && $content.length > 0) {
                    $content.summernote('code', merged);
                } else {
                    $content.val(merged);
                }
            } else {
                var rawContent = ($.fn && $.fn.summernote && $content.length > 0)
                    ? $content.summernote('code')
                    : $content.val();
                var clean = sanitizeContentForSummernote(rawContent);
                $builderPayload.val('');
                if ($.fn && $.fn.summernote && $content.length > 0) {
                    $content.summernote('code', clean);
                } else {
                    $content.val(clean);
                }
            }

            $modeHidden.val(state.mode);
        });

        if (config.tabFields && config.errorKeys && config.errorKeys.length > 0) {
            for (var tabId in config.tabFields) {
                if (!Object.prototype.hasOwnProperty.call(config.tabFields, tabId)) {
                    continue;
                }

                var fields = config.tabFields[tabId] || [];
                var hasError = fields.some(function (f) {
                    return config.errorKeys.includes(f);
                });

                if (hasError) {
                    var tabButton = document.querySelector('[data-bs-target="#' + tabId + '"]');
                    if (tabButton) {
                        bootstrap.Tab.getOrCreateInstance(tabButton).show();
                    }
                    break;
                }
            }
        }
    }

    window.CatminInitContentEditorMode = initContentEditorMode;

    var configNode = document.getElementById('catmin-editor-mode-config');
    if (configNode) {
        try {
            var config = JSON.parse(configNode.textContent || '{}');
            initContentEditorMode(config);
        } catch (_e) {
            // noop
        }
    }
}());
