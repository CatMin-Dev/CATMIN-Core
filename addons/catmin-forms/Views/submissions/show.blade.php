@extends('admin.layouts.catmin')

@section('page_title', 'Submission Detail')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Soumission #{{ $submission->id }}</h1>
        <p class="text-muted mb-0">{{ $submission->form->name ?? 'N/A' }}</p>
    </div>
    <a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-outline-secondary">Retour</a>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Statut:</strong> {{ $submission->status }}</p>
            <p><strong>Source:</strong> {{ $submission->source }}</p>
            <p><strong>Linked contact:</strong> {{ $submission->linked_contact_id ?: '—' }}</p>
            <p><strong>IP hash:</strong> {{ $submission->ip_hash ?: '—' }}</p>

            @if($submission->status !== 'processed')
                <form method="POST" action="{{ route('admin.forms.submissions.process', $submission->id) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-primary">Marquer traité</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><strong>Payload</strong></div>
        <div class="card-body">
            <pre class="mb-0">{{ json_encode($submission->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
</div>
@endsection
