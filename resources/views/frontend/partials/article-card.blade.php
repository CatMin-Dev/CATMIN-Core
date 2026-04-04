{{-- Article card partial — used in listings and home page --}}
@php
    $thumb = !empty($article['media_url']) ? $article['media_url'] : null;
    $slug  = $article['slug'] ?? '';
    $title = $article['title'] ?? '';
    $excerpt = $article['excerpt'] ?? '';
    $publishedAt = $article['published_at'] ?? null;
@endphp

<div class="cf-card cf-article-card h-100">
    @if($thumb)
        <a href="{{ route('frontend.articles.show', $slug) }}" tabindex="-1" aria-hidden="true">
            <img src="{{ $thumb }}"
                 alt="{{ e($title) }}"
                 class="cf-article-thumb"
                 loading="lazy">
        </a>
    @endif

    <div class="p-3">
        <p class="cf-article-meta mb-1">
            @if($publishedAt)
                <time datetime="{{ \Carbon\Carbon::parse($publishedAt)->toDateString() }}">
                    {{ \Carbon\Carbon::parse($publishedAt)->translatedFormat('d M Y') }}
                </time>
            @endif
        </p>

        <h3 class="h6 fw-semibold mb-2">
            <a href="{{ route('frontend.articles.show', $slug) }}"
               class="text-decoration-none text-body stretched-link">
                {{ $title }}
            </a>
        </h3>

        @if($excerpt)
            <p class="small text-muted mb-0">{{ \Illuminate\Support\Str::limit($excerpt, 120) }}</p>
        @endif
    </div>
</div>
