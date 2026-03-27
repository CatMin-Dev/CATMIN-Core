@extends('admin.layouts.catmin')

@section('page_title', 'Nouveau template mailer')

@section('content')
<x-admin.crud.page-header
    title="Creer un template"
    subtitle="Template V1 pour envois systeme; la mise en queue viendra plus tard."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('mailer.manage') }}">Retour Mailer</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Nouveau template</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('mailer.templates.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-lg-4"><label class="form-label" for="code">Code</label><input id="code" name="code" type="text" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}">@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-4"><label class="form-label" for="name">Nom</label><input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-4"><label class="form-label" for="subject">Sujet</label><input id="subject" name="subject" type="text" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required>@error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="body_html">Body HTML</label><textarea id="body_html" name="body_html" rows="8" class="form-control @error('body_html') is-invalid @enderror">{{ old('body_html') }}</textarea>@error('body_html')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="body_text">Body texte</label><textarea id="body_text" name="body_text" rows="5" class="form-control @error('body_text') is-invalid @enderror">{{ old('body_text') }}</textarea>@error('body_text')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 form-check ms-1"><input id="is_enabled" name="is_enabled" type="checkbox" class="form-check-input" value="1" @checked(old('is_enabled', true))><label class="form-check-label" for="is_enabled">Template actif</label></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Creer</button><a class="btn btn-outline-secondary" href="{{ admin_route('mailer.manage') }}">Annuler</a></div>
            </form>
        </div>
    </div>
</div>
@endsection
