@props([
    'inputName' => 'media_asset_id',
    'inputId' => 'media_asset_id',
    'label' => 'Media',
    'value' => null,
    'required' => false,
    'helpText' => 'Selectionnez un media depuis la bibliotheque.',
])

@php
    $selectedValue = old($inputName, $value);
@endphp

<div class="catmin-media-picker-field d-grid gap-2" data-media-picker-field>
    <label class="form-label fw-semibold" for="{{ $inputId }}">{{ $label }}@if($required) <span class="text-danger">*</span>@endif</label>

    <input id="{{ $inputId }}" name="{{ $inputName }}" type="hidden" value="{{ $selectedValue }}">

    <div class="catmin-media-picker-preview" data-media-picker-preview>
        <div class="catmin-media-picker-empty text-muted small" data-media-picker-empty>
            Aucun media selectionne.
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button
            type="button"
            class="btn btn-sm btn-outline-primary"
            data-media-picker-open
            data-input-id="{{ $inputId }}"
            data-bs-toggle="modal"
            data-bs-target="#catmin-media-picker-modal"
        >
            <i class="bi bi-collection me-1"></i>Choisir un media
        </button>

        <button type="button" class="btn btn-sm btn-outline-secondary" data-media-picker-clear>
            <i class="bi bi-x-circle me-1"></i>Retirer
        </button>
    </div>

    @error($inputName)
        <div class="text-danger small">{{ $message }}</div>
    @enderror

    <div class="form-text text-muted">{{ $helpText }}</div>
</div>
