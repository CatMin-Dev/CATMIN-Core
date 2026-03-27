@props([
    'title',
    'count' => null,
    'emptyMessage' => 'Aucune donnee.',
    'emptyColspan' => 1,
])

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">{{ $title }}</h2>
        @if($count !== null)
            <span class="badge text-bg-light">{{ $count }}</span>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                {{ $head }}
            </thead>
            <tbody>
                @if(trim((string) $rows) !== '')
                    {{ $rows }}
                @else
                    <tr>
                        <td colspan="{{ $emptyColspan }}" class="text-center text-muted py-4">{{ $emptyMessage }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
