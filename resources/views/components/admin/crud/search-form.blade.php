@props([
    'actionUrl',
    'query' => '',
    'placeholder' => 'Recherche...',
    'inputName' => 'q',
    'showReset' => true,
    'resetUrl' => null,
])

@php
    $queryValue = trim((string) $query);
    $resetLink = $resetUrl ?: $actionUrl;
@endphp

<form method="get" action="{{ $actionUrl }}" class="d-flex align-items-center gap-2">
    <div class="input-group input-group-sm" style="min-width: 280px;">
        <input
            type="search"
            name="{{ $inputName }}"
            class="form-control"
            placeholder="{{ $placeholder }}"
            value="{{ $queryValue }}"
        >
        <button class="btn btn-outline-secondary" type="submit" aria-label="Rechercher">
            <i class="bi bi-search"></i>
        </button>
    </div>

    @if($showReset && $queryValue !== '')
        <a class="btn btn-sm btn-outline-light border" href="{{ $resetLink }}" aria-label="Reset recherche">
            <i class="bi bi-x-lg"></i>
        </a>
    @endif
</form>
