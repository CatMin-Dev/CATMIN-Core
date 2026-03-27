@extends('admin.layouts.catmin')

@section('page_title', 'Blocks')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Blocks</h1>
        <p class="text-muted mb-0">Blocs reutilisables injectables dans les pages.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.blocks.create') }}">Nouveau bloc</a>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Blocs</h2>
            <span class="badge text-bg-light">{{ $blocks->count() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Statut</th><th>Apercu</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($blocks as $block)
                        <tr>
                            <td>{{ $block->name }}</td>
                            <td><code>{{ '{{ block:' . $block->slug . ' }}' }}</code></td>
                            <td><span class="badge {{ $block->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $block->status }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($block->content, 60) }}</td>
                            <td class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blocks.edit', $block) }}">Editer</a>
                                <form method="POST" action="{{ route('admin.blocks.toggle_status', $block) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Toggle</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun bloc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
