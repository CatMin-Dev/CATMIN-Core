@props([
    'title',
    'subtitle' => null,
])

<header {{ $attributes->class(['catmin-page-header', 'd-flex', 'flex-wrap', 'gap-3', 'justify-content-between', 'align-items-start']) }}>
    <div>
        <h1 class="h3 mb-1">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @if(trim((string) $slot) !== '')
        <div class="d-flex flex-wrap gap-2">
            {{ $slot }}
        </div>
    @endif
</header>
