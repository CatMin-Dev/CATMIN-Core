@props([
    'items' => [],
    'floating' => false,
    'defaultTimeout' => 7000,
    'stackClass' => '',
])

@php
    $rows = collect($items)
        ->filter(fn ($item) => is_array($item) && !empty($item['title']))
        ->values();

    $stackClasses = trim('catmin-notify-stack ' . ($floating ? 'catmin-notify-stack--floating ' : '') . $stackClass);
@endphp

@if($rows->isNotEmpty())
    <div {{ $attributes->class([$stackClasses]) }} data-catmin-notify-stack>
        @foreach($rows as $item)
            @php
                $severity = (string) ($item['severity'] ?? 'info');
                $variant = match ($severity) {
                    'critical' => 'danger',
                    'warning' => 'warning',
                    'success' => 'success',
                    default => 'info',
                };
                $timeout = max(0, (int) ($item['timeout'] ?? $defaultTimeout));
                $canOpen = !empty($item['url']) && (empty($item['permission']) || catmin_can($item['permission']));
                $icon = match ($variant) {
                    'danger' => 'bi bi-shield-exclamation',
                    'warning' => 'bi bi-exclamation-triangle',
                    'success' => 'bi bi-check-circle',
                    default => 'bi bi-info-circle',
                };
            @endphp

            <article
                class="catmin-notify catmin-notify--{{ $variant }}"
                role="status"
                aria-live="polite"
                data-catmin-notification
                data-timeout="{{ $timeout }}"
            >
                <div class="catmin-notify__icon" aria-hidden="true">
                    <i class="{{ $icon }}"></i>
                </div>

                <div class="catmin-notify__body">
                    <p class="catmin-notify__title mb-0">{{ $item['title'] }}</p>
                    @if(!empty($item['message']))
                        <p class="catmin-notify__message mb-0">{{ $item['message'] }}</p>
                    @endif

                    @if($canOpen)
                        <a class="catmin-notify__link" href="{{ $item['url'] }}">Ouvrir</a>
                    @endif
                </div>

                <button
                    type="button"
                    class="catmin-notify__close"
                    aria-label="Fermer la notification"
                    data-catmin-notification-close
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>

                @if($timeout > 0)
                    <div class="catmin-notify__timer" data-catmin-notification-timer></div>
                @endif
            </article>
        @endforeach
    </div>
@endif
