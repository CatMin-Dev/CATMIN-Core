@extends('admin.layouts.catmin')

@section('page_title', 'CRM · Contacts')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Contacts</h1>
        <p class="text-muted mb-0">Recherche rapide, gestion des contacts et accès à la timeline.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.crm.contacts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="q" class="form-control" placeholder="Nom, email, téléphone, entreprise" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pipeline</label>
                    <select class="form-select" name="pipeline_stage">
                        <option value="">Toutes</option>
                        @foreach($pipelineStages as $stage)
                            <option value="{{ $stage }}" @selected(request('pipeline_stage') === $stage)>{{ ucfirst($stage) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Source</label>
                    <input type="text" name="source" class="form-control" value="{{ request('source') }}" placeholder="admin, forms...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Interaction de</label>
                    <input type="date" name="interaction_from" class="form-control" value="{{ request('interaction_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">à</label>
                    <input type="date" name="interaction_to" class="form-control" value="{{ request('interaction_to') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">Rechercher</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap gap-2">
            @foreach($pipelineMetrics as $stage => $count)
                <a href="{{ route('admin.crm.contacts.index', ['pipeline_stage' => $stage]) }}" class="btn btn-sm btn-outline-secondary">
                    {{ ucfirst($stage) }} <span class="badge text-bg-light ms-1">{{ $count }}</span>
                </a>
            @endforeach
            <a href="{{ route('admin.crm.pipeline.index') }}" class="btn btn-sm btn-primary ms-auto">Vue pipeline</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-white"><strong>Nouveau contact</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.contacts.store') }}" class="row g-3">
                        @csrf
                        <div class="col-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="last_name" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Entreprise</label>
                            <select name="crm_company_id" class="form-select">
                                <option value="">Aucune</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pipeline</label>
                            <select name="pipeline_stage" class="form-select">
                                @foreach($pipelineStages as $stage)
                                    <option value="{{ $stage }}" @selected($stage === 'new')>{{ ucfirst($stage) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Source</label>
                            <input type="text" name="source" class="form-control" value="admin">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Liste des contacts</strong>
                    <span class="badge text-bg-light">{{ $contacts->total() }}</span>
                </div>
                <div class="table-responsive catmin-table-scroll">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th>Entreprise</th>
                                <th>Pipeline</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contacts as $contact)
                                <tr>
                                    <td>
                                        <strong>{{ $contact->fullName() }}</strong><br>
                                        <small class="text-muted">{{ $contact->email ?? '—' }}</small>
                                    </td>
                                    <td>{{ $contact->company->name ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.crm.contacts.pipeline.move', $contact->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="pipeline_stage" class="form-select form-select-sm" onchange="this.form.submit()">
                                                @foreach($pipelineStages as $stage)
                                                    <option value="{{ $stage }}" @selected(($contact->pipeline_stage ?? 'new') === $stage)>{{ ucfirst($stage) }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td><span class="badge text-bg-light">{{ ucfirst($contact->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.crm.contacts.show', $contact->id) }}" class="btn btn-sm btn-outline-primary">Fiche</a>
                                        <form method="POST" action="{{ route('admin.crm.contacts.destroy', $contact->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce contact ?')">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Aucun contact.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($contacts->hasPages())
                    <div class="card-footer">{{ $contacts->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
