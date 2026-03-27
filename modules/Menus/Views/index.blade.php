@extends('admin.layouts.catmin')

@section('page_title', 'Menus')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Menus</h1>
        <p class="text-muted mb-0">Gestion des menus dynamiques frontend.</p>
    </div>
    @if(catmin_can('module.menus.create'))
    <a class="btn btn-primary" href="{{ route('admin.menus.create') }}">Nouveau menu</a>
    @endif
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Menus</h2>
            <span class="badge text-bg-light">{{ $menus->count() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Location</th><th>Statut</th><th>Items</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($menus as $menu)
                        <tr>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $menu->slug }}</td>
                            <td>{{ $menu->location }}</td>
                            <td><span class="badge {{ $menu->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $menu->status }}</span></td>
                            <td>{{ $menu->items_count }}</td>
                            <td class="d-flex gap-2">
                                @if(catmin_can('module.menus.edit'))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.menus.edit', $menu) }}">Editer</a>
                                <form method="POST" action="{{ route('admin.menus.toggle_status', $menu) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Toggle</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun menu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
