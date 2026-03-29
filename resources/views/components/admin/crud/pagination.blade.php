@props([
    'paginator',
    'routeName' => null,
    'routeParams' => [],
    'query' => [],
])

@if($paginator->hasPages())
    @php
        $start = max(1, $paginator->currentPage() - 2);
        $end = min($paginator->lastPage(), $paginator->currentPage() + 2);

        $buildUrl = static function (int $page) use ($paginator, $routeName, $routeParams, $query): string {
            if (is_string($routeName) && $routeName !== '') {
                $params = array_merge($routeParams, ['page' => $page], array_filter(
                    $query,
                    static fn ($value): bool => $value !== '' && $value !== null
                ));

                return route($routeName, $params);
            }

            return $paginator->url($page);
        };

        $previousUrl = $paginator->onFirstPage() ? null : $buildUrl(max(1, $paginator->currentPage() - 1));
        $nextUrl = $paginator->hasMorePages() ? $buildUrl(min($paginator->lastPage(), $paginator->currentPage() + 1)) : null;
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="text-muted small">
            Affichage {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} sur {{ $paginator->total() }}
        </div>

        <nav aria-label="Pagination" class="ms-md-auto">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if($paginator->onFirstPage())
                        <span class="page-link" aria-hidden="true">&laquo;</span>
                    @else
                        <a class="page-link" href="{{ $previousUrl }}" rel="prev" aria-label="Page precedente">&laquo;</a>
                    @endif
                </li>

                @if($start > 1)
                    <li class="page-item"><a class="page-link" href="{{ $buildUrl(1) }}">1</a></li>
                    @if($start > 2)
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}" @if($page === $paginator->currentPage()) aria-current="page" @endif>
                        @if($page === $paginator->currentPage())
                            <span class="page-link">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $buildUrl($page) }}">{{ $page }}</a>
                        @endif
                    </li>
                @endfor

                @if($end < $paginator->lastPage())
                    @if($end < $paginator->lastPage() - 1)
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @endif
                    <li class="page-item"><a class="page-link" href="{{ $buildUrl($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a></li>
                @endif

                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    @if($paginator->hasMorePages())
                        <a class="page-link" href="{{ $nextUrl }}" rel="next" aria-label="Page suivante">&raquo;</a>
                    @else
                        <span class="page-link" aria-hidden="true">&raquo;</span>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
@endif
