@extends('admin.layouts.catmin')

@section('page_title', 'CRM · Entreprises')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Entreprises</h1>
        <p class="text-muted mb-0">Annuaire entreprise lié aux contacts CRM.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-white"><strong>Nouvelle entreprise</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.companies.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Website</label><input type="url" name="website" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Secteur</label><input type="text" name="industry" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Téléphone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="col-12"><button class="btn btn-primary">Créer</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Liste des entreprises</strong>
                    <span class="badge text-bg-light">{{ $companies->total() }}</span>
                </div>
                <div class="table-responsive catmin-table-scroll">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead><tr><th>Nom</th><th>Secteur</th><th>Contacts</th><th></th></tr></thead>
                        <tbody>
                            @forelse($companies as $company)
                                <tr>
                                    <td><strong>{{ $company->name }}</strong><br><small class="text-muted">{{ $company->email }}</small></td>
                                    <td>{{ $company->industry ?? '—' }}</td>
                                    <td>{{ $company->contacts_count }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.crm.companies.destroy', $company->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette entreprise ?')">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Aucune entreprise.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($companies->hasPages())<div class="card-footer">{{ $companies->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
