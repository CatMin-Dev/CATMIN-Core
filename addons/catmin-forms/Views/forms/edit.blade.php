@extends('admin.layouts.catmin')

@section('page_title', 'Edit Form')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">{{ $formItem->name }}</h1>
        <p class="text-muted mb-0">Edition formulaire et champs.</p>
    </div>
    <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary">Retour</a>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-header bg-white"><strong>Configuration</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.forms.update', $formItem->id) }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-4"><label class="form-label">Nom</label><input class="form-control" name="name" value="{{ $formItem->name }}" required></div>
                <div class="col-md-2"><label class="form-label">Type</label><input class="form-control" name="type" value="{{ $formItem->type }}" required></div>
                <div class="col-md-2"><label class="form-label">Statut</label><input class="form-control" name="status" value="{{ $formItem->status }}" required></div>
                <div class="col-md-2"><label class="form-label">Mapping</label><input class="form-control" name="mapping" value="{{ $formItem->config['mapping'] ?? 'none' }}"></div>
                <div class="col-md-2"><label class="form-label">Target ID</label><input class="form-control" type="number" name="target_id" value="{{ $formItem->config['target_id'] ?? '' }}"></div>
                <div class="col-12"><button class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white"><strong>Ajouter un champ</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.forms.fields.store', $formItem->id) }}" class="row g-3">
                @csrf
                <div class="col-md-2"><input class="form-control" name="type" placeholder="type" required></div>
                <div class="col-md-2"><input class="form-control" name="label" placeholder="label" required></div>
                <div class="col-md-2"><input class="form-control" name="key" placeholder="key" required></div>
                <div class="col-md-2"><input class="form-control" name="validation_rules" placeholder="email|max:191"></div>
                <div class="col-md-2"><input class="form-control" type="number" name="sort_order" value="0"></div>
                <div class="col-md-1 form-check d-flex align-items-center"><input class="form-check-input" type="checkbox" name="is_required" value="1"></div>
                <div class="col-md-1"><button class="btn btn-outline-primary w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><strong>Champs</strong></div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Key</th><th>Label</th><th>Type</th><th>Rules</th><th></th></tr></thead>
                <tbody>
                    @forelse($formItem->fields as $field)
                        <tr>
                            <td>{{ $field->key }}</td>
                            <td>{{ $field->label }}</td>
                            <td>{{ $field->type }}</td>
                            <td>{{ $field->validation_rules }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.forms.fields.destroy', [$formItem->id, $field->id]) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun champ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
