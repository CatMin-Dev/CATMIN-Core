@extends('admin.layouts.catmin')

@section('page_title', 'Forms')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Formulaires</h1>
        <p class="text-muted mb-0">CRUD des formulaires publics et mappings métiers.</p>
    </div>
    <a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-outline-secondary">Soumissions</a>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-header bg-white"><strong>Nouveau formulaire</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.forms.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Nom</label><input name="name" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Slug</label><input name="slug" class="form-control"></div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        @foreach(['contact','lead','event_request','booking_request','custom'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mapping</label>
                    <select name="mapping" class="form-select">
                        @foreach(['none','crm_lead','event_preregistration','booking_request'] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Target ID</label><input type="number" min="1" name="target_id" class="form-control"></div>
                <div class="col-12"><button class="btn btn-primary">Créer</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><strong>Liste</strong></div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Nom</th><th>Type</th><th>Statut</th><th>Mapping</th><th>Soumissions</th><th></th></tr></thead>
                <tbody>
                    @forelse($forms as $form)
                        <tr>
                            <td><strong>{{ $form->name }}</strong><br><small class="text-muted">/{{ 'forms/' . $form->slug }}</small></td>
                            <td>{{ $form->type }}</td>
                            <td>{{ $form->status }}</td>
                            <td>{{ $form->config['mapping'] ?? 'none' }}</td>
                            <td>{{ $form->submissions_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.forms.edit', $form->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.forms.destroy', $form->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun formulaire.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($forms->hasPages())<div class="card-footer">{{ $forms->links() }}</div>@endif
    </div>
</div>
@endsection
