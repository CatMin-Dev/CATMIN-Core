@extends('admin.layouts.catmin')

@section('page_title', 'CRM · Fiche contact')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Fiche contact</h1>
        <p class="text-muted mb-0">{{ $contact->fullName() }}</p>
    </div>
    <a href="{{ route('admin.crm.contacts.index') }}" class="btn btn-outline-secondary">Retour</a>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card mb-4">
                <div class="card-header bg-white"><strong>Informations contact</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.contacts.update', $contact->id) }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="first_name" class="form-control" value="{{ $contact->first_name }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="last_name" class="form-control" value="{{ $contact->last_name }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $contact->email }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control" value="{{ $contact->phone }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="{{ $contact->position }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                @foreach(['lead','active','inactive'] as $status)
                                    <option value="{{ $status }}" @selected($contact->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><strong>Envoyer email (mailer)</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.contacts.mail.send', $contact->id) }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Sujet</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card mb-4">
                <div class="card-header bg-white"><strong>Ajouter une note</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.crm.contacts.notes.store', $contact->id) }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="note">Note</option>
                                <option value="call">Call</option>
                                <option value="meeting">Meeting</option>
                                <option value="mail">Mail</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Contenu</label>
                            <input type="text" name="content" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Timeline interactions</strong>
                    <span class="badge text-bg-light">{{ count($timeline) }}</span>
                </div>
                <div class="card-body">
                    @forelse($timeline as $item)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong>{{ $item['title'] }}</strong>
                                <small class="text-muted">{{ $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') : '—' }}</small>
                            </div>
                            <div class="small text-uppercase text-muted mb-2">{{ $item['source'] }} · {{ $item['type'] }}</div>
                            <div>{{ $item['content'] }}</div>
                        </div>
                    @empty
                        <div class="text-muted">Aucune interaction.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
