@extends('frontend.layouts.base')

@section('meta_title', $event->title . ' - ' . $siteName)
@section('meta_description', (string) str((string) $event->description)->stripTags()->limit(160))

@section('content')
<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <article class="card border-0 shadow-sm">
                @if($event->featured_image)
                    <img src="{{ $event->featured_image }}" class="card-img-top" alt="{{ $event->title }}" style="max-height: 420px; object-fit: cover;">
                @endif
                <div class="card-body p-4">
                    <h1 class="h2 mb-3">{{ $event->title }}</h1>
                    <div class="d-flex flex-wrap gap-2 mb-3 text-muted small">
                        <span class="badge text-bg-light">{{ ucfirst($state['status']) }}</span>
                        <span>{{ optional($event->start_at)->format('d/m/Y H:i') }} - {{ optional($event->end_at)->format('d/m/Y H:i') }}</span>
                        @if($event->location)
                            <span>{{ $event->location }}</span>
                        @endif
                    </div>

                    @if($event->address)
                        <p class="text-muted mb-3">{{ $event->address }}</p>
                    @endif

                    @if($event->description)
                        <div class="cf-prose">{!! $event->description !!}</div>
                    @endif
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <aside class="card border-0 shadow-sm sticky-top" style="top: 1.5rem;">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Participation</h2>

                    @if($state['remaining'] !== null)
                        <p class="mb-2">Places restantes: <strong>{{ $state['remaining'] }}</strong></p>
                    @else
                        <p class="mb-2">Capacite: <strong>Illimitee</strong></p>
                    @endif

                    @if($state['status'] === 'finished')
                        <div class="alert alert-secondary mb-0">Cet evenement est termine.</div>
                    @elseif($state['status'] === 'cancelled')
                        <div class="alert alert-danger mb-0">Cet evenement est annule.</div>
                    @elseif($state['status'] === 'sold_out' && !($event->allow_waitlist ?? false))
                        <div class="alert alert-warning mb-0">Complet. Aucune inscription possible.</div>
                    @endif

                    @if(($state['cta']['action'] ?? 'none') === 'shop' && !empty($state['cta']['url']))
                        <a class="btn btn-primary w-100 mt-3" href="{{ $state['cta']['url'] }}">
                            {{ $state['cta']['label'] }}
                        </a>
                        <p class="small text-muted mt-2 mb-0">Achat gere via la boutique.</p>
                    @elseif(($state['cta']['action'] ?? 'none') === 'external' && !empty($state['cta']['url']))
                        <a class="btn btn-primary w-100 mt-3" href="{{ $state['cta']['url'] }}" target="_blank" rel="noopener">
                            {{ $state['cta']['label'] }}
                        </a>
                    @elseif(($state['cta']['action'] ?? 'none') === 'register')
                        <form method="POST" action="{{ route('frontend.events.register', $event->slug) }}" class="mt-3" novalidate>
                            @csrf
                            <input type="hidden" name="form_token" value="{{ $formToken }}">

                            @if($errors->has('global'))
                                <div class="alert alert-danger">{{ $errors->first('global') }}</div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Nom complet</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telephone (optionnel)</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nombre de places</label>
                                <input type="number" min="1" max="{{ max(1, (int) ($event->max_places_per_registration ?? 1)) }}" class="form-control @error('seats_count') is-invalid @enderror" name="seats_count" value="{{ old('seats_count', 1) }}">
                                @error('seats_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" value="1" id="consent" name="consent" required>
                                <label class="form-check-label" for="consent">J'accepte d'etre contacte pour cette inscription.</label>
                                @error('consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button class="btn btn-primary w-100" type="submit">{{ $state['cta']['label'] }}</button>
                        </form>
                    @else
                        <div class="alert alert-secondary mt-3 mb-0">Inscription indisponible actuellement.</div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
