@php
    $editorModeConfig = [
        'formId' => $formId ?? 'content-form',
        'tabFields' => $tabFields ?? [],
        'errorKeys' => array_keys($errors->messages()),
    ];
@endphp

<script type="application/json" id="catmin-editor-mode-config">{!! json_encode($editorModeConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="{{ asset('assets/catmin/content-editor-mode.js') }}"></script>
