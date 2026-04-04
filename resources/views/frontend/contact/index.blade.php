@extends('frontend.layouts.base')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <nav aria-label="Fil d'Ariane" class="mb-4">
                <ol class="breadcrumb cf-breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.home') }}">Accueil</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Contact</li>
                </ol>
            </nav>

            <div class="cf-card cf-contact-card p-4 p-lg-5">

                <h1 class="h3 mb-1">Nous contacter</h1>
                <p class="text-muted mb-4">Remplissez le formulaire ci-dessous, nous vous répondrons dès que possible.</p>

                @if($errors->any())
                    <div class="alert alert-danger cf-flash">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('frontend.contact.send') }}" novalidate>
                    @csrf

                    {{-- Name --}}
                    <div class="mb-3">
                        <label for="cf-name" class="form-label fw-medium">
                            Nom <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="cf-name"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               maxlength="120"
                               required
                               autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="cf-email" class="form-label fw-medium">
                            Adresse e-mail <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="email"
                               id="cf-email"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               maxlength="254"
                               required
                               autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Subject --}}
                    <div class="mb-3">
                        <label for="cf-subject" class="form-label fw-medium">Sujet</label>
                        <input type="text"
                               id="cf-subject"
                               name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}"
                               maxlength="200"
                               autocomplete="off">
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Message --}}
                    <div class="mb-4">
                        <label for="cf-message" class="form-label fw-medium">
                            Message <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <textarea id="cf-message"
                                  name="message"
                                  rows="6"
                                  class="form-control @error('message') is-invalid @enderror"
                                  maxlength="{{ config('catmin_frontend.contact_max_chars', 2000) }}"
                                  required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Envoyer le message
                    </button>
                </form>

            </div>

        </div>
    </div>
</div>
@endsection
