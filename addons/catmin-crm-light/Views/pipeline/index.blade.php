@extends('admin.layouts.catmin')

@section('page_title', 'CRM · Pipeline')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Pipeline CRM</h1>
        <p class="text-muted mb-0">Vue simplifiée des étapes commerciales.</p>
    </div>
    <a href="{{ route('admin.crm.contacts.index') }}" class="btn btn-outline-secondary">Retour contacts</a>
</header>

<div class="catmin-page-body">
    <div class="row g-3 mb-4">
        @foreach($pipelineMetrics as $stage => $count)
            <div class="col-6 col-md-2">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ ucfirst($stage) }}</div>
                        <div class="h4 mb-0">{{ $count }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        @foreach($pipelineStages as $stage)
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong>{{ ucfirst($stage) }}</strong>
                        <span class="badge text-bg-light">{{ $contactsByStage[$stage]->total() }}</span>
                    </div>
                    <div class="card-body" style="max-height: 420px; overflow: auto;">
                        @forelse($contactsByStage[$stage] as $contact)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $contact->fullName() }}</div>
                                <div class="small text-muted">{{ $contact->email ?: 'sans email' }}</div>
                                <a href="{{ route('admin.crm.contacts.show', $contact->id) }}" class="btn btn-sm btn-outline-primary mt-2">Ouvrir</a>
                            </div>
                        @empty
                            <div class="text-muted">Aucun contact.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
