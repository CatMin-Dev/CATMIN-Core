@extends('admin.layouts.catmin')

@section('page_title', 'Form Submissions')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Soumissions</h1>
        <p class="text-muted mb-0">Suivi des demandes publiques.</p>
    </div>
    <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary">Forms</a>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Formulaire</label>
                    <select name="form_definition_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach($forms as $f)
                            <option value="{{ $f->id }}" @selected((string) request('form_definition_id') === (string) $f->id)>{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="new" @selected(request('status') === 'new')>new</option>
                        <option value="processed" @selected(request('status') === 'processed')>processed</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrer</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>ID</th><th>Form</th><th>Statut</th><th>Linked CRM</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td>#{{ $submission->id }}</td>
                            <td>{{ $submission->form->name ?? 'N/A' }}</td>
                            <td><span class="badge text-bg-light">{{ $submission->status }}</span></td>
                            <td>{{ $submission->linked_contact_id ?: '—' }}</td>
                            <td>{{ $submission->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.forms.submissions.show', $submission->id) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune soumission.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($submissions->hasPages())<div class="card-footer">{{ $submissions->links() }}</div>@endif
    </div>
</div>
@endsection
