@extends('admin.layouts.catmin')

@section('page_title', 'Mailer')

@section('content')
<x-admin.crud.page-header
    title="Mailer"
    subtitle="Base V1: configuration d'envoi, templates, historique minimal. Queue a venir."
>
    @if(catmin_can('module.mailer.create'))
        <a class="btn btn-primary" href="{{ admin_route('mailer.templates.create') }}">Nouveau template</a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card mb-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Configuration d'envoi</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('mailer.config.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-lg-3"><label class="form-label" for="driver">Driver</label><select id="driver" name="driver" class="form-select @error('driver') is-invalid @enderror"><option value="smtp" @selected(old('driver', $config->driver) === 'smtp')>SMTP</option><option value="mailgun" @selected(old('driver', $config->driver) === 'mailgun')>Mailgun</option><option value="ses" @selected(old('driver', $config->driver) === 'ses')>SES</option><option value="log" @selected(old('driver', $config->driver) === 'log')>Log</option></select>@error('driver')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="from_email">From email</label><input id="from_email" name="from_email" type="email" class="form-control @error('from_email') is-invalid @enderror" value="{{ old('from_email', $config->from_email) }}">@error('from_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="from_name">From name</label><input id="from_name" name="from_name" type="text" class="form-control @error('from_name') is-invalid @enderror" value="{{ old('from_name', $config->from_name) }}">@error('from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="reply_to_email">Reply-to</label><input id="reply_to_email" name="reply_to_email" type="email" class="form-control @error('reply_to_email') is-invalid @enderror" value="{{ old('reply_to_email', $config->reply_to_email) }}">@error('reply_to_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 form-check ms-1"><input id="is_enabled" name="is_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('is_enabled', $config->is_enabled))><label class="form-check-label" for="is_enabled">Activer le module d'envoi</label></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer config</button></div>
            </form>
        </div>
    </div>

    <x-admin.crud.table-card
        title="Templates"
        :count="$templates->count()"
        :empty-colspan="6"
        empty-message="Aucun template defini."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Nom</th>
                <th>Sujet</th>
                <th>Actif</th>
                <th class="text-end">Actions</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($templates as $template)
                <tr>
                    <td>{{ $template->id }}</td>
                    <td>{{ $template->code }}</td>
                    <td>{{ $template->name }}</td>
                    <td>{{ $template->subject }}</td>
                    <td><span class="badge {{ $template->is_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $template->is_enabled ? 'Oui' : 'Non' }}</span></td>
                    <td class="text-end">
                        @if(catmin_can('module.mailer.edit'))
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('mailer.templates.edit', ['template' => $template->id]) }}">Modifier</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    <x-admin.crud.table-card
        title="Historique recent"
        :count="$history->count()"
        :empty-colspan="5"
        empty-message="Aucun envoi historise en V1."
        class="mt-4"
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Destinataire</th>
                <th>Sujet</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($history as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->recipient }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ optional($row->sent_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
