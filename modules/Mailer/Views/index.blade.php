@extends('admin.layouts.catmin')

@section('page_title', 'Mailer')

@section('content')
<x-admin.crud.page-header
    title="Mailer"
    subtitle="Templates dynamiques, email de test, queue et journal d envoi centralises."
>
    @if(catmin_can('module.mailer.create'))
        <a class="btn btn-primary" href="{{ admin_route('mailer.templates.create') }}">Nouveau template</a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Configuration d'envoi, branding et sandbox</h2></div>
                <div class="card-body">
                    <form method="post" action="{{ admin_route('mailer.config.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-12 col-lg-3"><label class="form-label" for="driver">Driver</label><select id="driver" name="driver" class="form-select @error('driver') is-invalid @enderror"><option value="smtp" @selected(old('driver', $config->driver) === 'smtp')>SMTP</option><option value="mailgun" @selected(old('driver', $config->driver) === 'mailgun')>Mailgun</option><option value="ses" @selected(old('driver', $config->driver) === 'ses')>SES</option><option value="log" @selected(old('driver', $config->driver) === 'log')>Log</option></select>@error('driver')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-lg-3"><label class="form-label" for="from_email">From email</label><input id="from_email" name="from_email" type="email" class="form-control @error('from_email') is-invalid @enderror" value="{{ old('from_email', $config->from_email) }}">@error('from_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-lg-3"><label class="form-label" for="from_name">From name</label><input id="from_name" name="from_name" type="text" class="form-control @error('from_name') is-invalid @enderror" value="{{ old('from_name', $config->from_name) }}">@error('from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-lg-3"><label class="form-label" for="reply_to_email">Reply-to</label><input id="reply_to_email" name="reply_to_email" type="email" class="form-control @error('reply_to_email') is-invalid @enderror" value="{{ old('reply_to_email', $config->reply_to_email) }}">@error('reply_to_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-lg-4"><label class="form-label" for="brand_name">Nom de marque</label><input id="brand_name" name="brand_name" type="text" class="form-control @error('brand_name') is-invalid @enderror" value="{{ old('brand_name', $config->brand_name) }}">@error('brand_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-lg-4"><label class="form-label" for="brand_logo_url">Logo URL</label><input id="brand_logo_url" name="brand_logo_url" type="url" class="form-control @error('brand_logo_url') is-invalid @enderror" value="{{ old('brand_logo_url', $config->brand_logo_url) }}" placeholder="https://...">@error('brand_logo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-lg-4"><label class="form-label" for="brand_primary_color">Couleur primaire</label><input id="brand_primary_color" name="brand_primary_color" type="text" class="form-control @error('brand_primary_color') is-invalid @enderror" value="{{ old('brand_primary_color', $config->brand_primary_color ?: '#0d6efd') }}" placeholder="#0d6efd">@error('brand_primary_color')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label" for="brand_footer_text">Texte pied d'email</label><textarea id="brand_footer_text" name="brand_footer_text" rows="2" class="form-control @error('brand_footer_text') is-invalid @enderror">{{ old('brand_footer_text', $config->brand_footer_text) }}</textarea>@error('brand_footer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 form-check ms-1"><input id="sandbox_mode" name="sandbox_mode" type="checkbox" class="form-check-input" value="1" @checked(old('sandbox_mode', $config->sandbox_mode))><label class="form-check-label" for="sandbox_mode">Mode sandbox (redirige tous les envois)</label></div>
                        <div class="col-12 col-lg-6"><label class="form-label" for="sandbox_recipient">Destinataire sandbox</label><input id="sandbox_recipient" name="sandbox_recipient" type="email" class="form-control @error('sandbox_recipient') is-invalid @enderror" value="{{ old('sandbox_recipient', $config->sandbox_recipient) }}" placeholder="qa@exemple.com">@error('sandbox_recipient')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 form-check ms-1"><input id="is_enabled" name="is_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('is_enabled', $config->is_enabled))><label class="form-check-label" for="is_enabled">Activer le module d'envoi</label></div>
                        <div class="col-12"><p class="small text-muted mb-0">En sandbox, l'historique conserve le destinataire original dans les variables et indique la redirection.</p></div>
                        <div class="col-12"><button class="btn btn-primary" type="submit">Enregistrer config</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Email de test</h2></div>
                <div class="card-body">
                    <form method="post" action="{{ admin_route('mailer.test') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="template_id">Template</label>
                            <select id="template_id" name="template_id" class="form-select">
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @selected((string) old('template_id', $defaultTemplateId) === (string) $template->id)>{{ $template->name }} ({{ $template->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="recipient">Destinataire</label>
                            <input id="recipient" name="recipient" type="email" class="form-control" value="{{ old('recipient') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="sample_payload">Payload JSON</label>
                            <textarea id="sample_payload" name="sample_payload" rows="6" class="form-control">{{ old('sample_payload') }}</textarea>
                            <div class="form-text">Laisse vide pour utiliser le payload exemple du template.</div>
                        </div>
                        <div class="col-12 form-check ms-1">
                            <input id="queue" name="queue" type="checkbox" class="form-check-input" value="1" @checked(old('queue'))>
                            <label class="form-check-label" for="queue">Passer par la queue</label>
                        </div>
                        <div class="col-12"><button class="btn btn-outline-primary" type="submit">Lancer un test</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-admin.crud.table-card
        title="Templates"
        :count="$templates->count()"
        :empty-colspan="7"
        empty-message="Aucun template defini."
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Nom</th>
                <th>Variables</th>
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
                    <td><div class="fw-semibold">{{ $template->name }}</div><div class="small text-muted">{{ $template->description ?: '—' }}</div></td>
                    <td>{{ collect($template->available_variables ?? [])->join(', ') ?: '—' }}</td>
                    <td>{{ $template->subject }}</td>
                    <td><span class="badge {{ $template->is_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $template->is_enabled ? 'Oui' : 'Non' }}</span></td>
                    <td class="text-end">
                        @if(catmin_can('module.mailer.edit'))
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('mailer.templates.edit', ['template' => $template->id]) }}">Modifier / preview</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    <x-admin.crud.table-card
        title="Journal d'envoi"
        :count="$history->total()"
        :empty-colspan="8"
        empty-message="Aucun envoi historise."
        class="mt-4"
    >
        <x-slot:head>
            <tr>
                <th>ID</th>
                <th>Destinataire</th>
                <th>Template</th>
                <th>Driver</th>
                <th>Statut</th>
                <th>Source</th>
                <th>Tentatives</th>
                <th>Date</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($history as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td><div>{{ $row->recipient }}</div><div class="small text-muted">{{ $row->recipient_name ?: '—' }}</div></td>
                    <td><div>{{ $row->template_code ?: 'manuel' }}</div><div class="small text-muted">{{ $row->subject }}</div></td>
                    <td>{{ $row->driver ?: '—' }}</td>
                    <td><span class="badge {{ $row->status === 'sent' ? 'text-bg-success' : ($row->status === 'failed' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ $row->status }}</span></td>
                    <td>{{ $row->trigger_source ?: '—' }}</td>
                    <td>{{ $row->attempts }}</td>
                    <td>
                        <div>{{ optional($row->sent_at ?? $row->queued_at ?? $row->created_at)->format('d/m/Y H:i') }}</div>
                        @if($row->error_message)
                            <div class="small text-danger">{{ $row->error_message }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
    @if($history->hasPages())
        <div class="mt-3">{{ $history->links() }}</div>
    @endif
</div>
@endsection
