@extends('frontend.layouts.base')

@section('meta_title', 'Confirmation - ' . $event->title . ' - ' . $siteName)

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-3">Inscription enregistree</h1>
                    <p class="mb-2"><strong>Evenement:</strong> {{ $event->title }}</p>
                    <p class="mb-2"><strong>Participant:</strong> {{ $participant->fullName() }}</p>
                    <p class="mb-2"><strong>Email:</strong> {{ $participant->email }}</p>
                    <p class="mb-4"><strong>Statut:</strong> <span class="badge text-bg-light">{{ ucfirst($participant->status) }}</span></p>

                    <a class="btn btn-primary" href="{{ route('frontend.events.show', $event->slug) }}">Retour a l'evenement</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
