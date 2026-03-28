@php
    $editorModeConfig = [
        'formId' => $formId ?? 'content-form',
        'tabFields' => $tabFields ?? [],
        'errorKeys' => array_keys($errors->messages()),
    ];
@endphp

<script type="application/json" id="catmin-editor-mode-config">{!! json_encode($editorModeConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="{{ asset('assets/catmin/content-editor-mode.js') }}"></script>
<script>
(function () {
    const cfgNode = document.getElementById('catmin-editor-mode-config');
    if (!cfgNode) {
        return;
    }

    let cfg = {};
    try {
        cfg = JSON.parse(cfgNode.textContent || '{}');
    } catch (_e) {
        cfg = {};
    }

    function fallbackInit() {
        if (!window.jQuery) {
            return;
        }

        const $ = window.jQuery;
        const formId = cfg.formId || 'content-form';
        const $mode = $('#editor_mode');
        const $summernoteRadio = $('#editor-mode-summernote');
        const $builderRadio = $('#editor-mode-builder');
        const $summernoteWrapper = $('#summernote-wrapper');
        const $builderWrapper = $('#builder-wrapper');
        const $content = $('#content');

        if ($mode.length === 0 || $summernoteRadio.length === 0 || $builderRadio.length === 0) {
            return;
        }

        const mode = ($mode.val() || 'summernote') === 'builder' ? 'builder' : 'summernote';
        $summernoteRadio.prop('checked', mode === 'summernote');
        $builderRadio.prop('checked', mode === 'builder');
        $summernoteWrapper.toggleClass('d-none', mode === 'builder');
        $builderWrapper.toggleClass('d-none', mode !== 'builder');

        if ($.fn && $.fn.summernote && $content.length > 0 && $content.next('.note-editor').length === 0) {
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
                    [ 'view', [ 'fullscreen', 'codeview', 'help' ] ],
                ],
            });
        }

        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function () {
                $mode.val($builderRadio.is(':checked') ? 'builder' : 'summernote');
            });
        }
    }

    function boot() {
        if (typeof window.CatminInitContentEditorMode === 'function') {
            window.CatminInitContentEditorMode(cfg);
            return;
        }

        fallbackInit();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());
</script>
