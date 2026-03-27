@props([
    'title',
    'count' => null,
    'emptyMessage' => 'Aucune donnee.',
    'emptyColspan' => 1,
])

@php
    $headSlot = isset($head) ? (string) $head : '';
    $rowsSlot = isset($rows) ? (string) $rows : '';
@endphp

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
                {!! $headSlot !!}
            </thead>
            <tbody>
                @if(trim($rowsSlot) !== '')
                    {!! $rowsSlot !!}
                @else
                    <tr>
                        <td colspan="{{ $emptyColspan }}" class="text-center text-muted py-4">{{ $emptyMessage }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
