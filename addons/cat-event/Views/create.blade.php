@extends('admin.layouts.catmin')

@section('page_title', 'Nouvel événement')

@section('content')
<header class="catmin-page-header">
    <div>
        <h1 class="h3 mb-1">Nouvel événement</h1>
        <a href="{{ route('admin.events.index') }}" class="text-muted small">← Retour à la liste</a>
    </div>
</header>

<div class="catmin-page-body">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.events.store') }}">
        @csrf
        @include('cat-event::_form', ['event' => null, 'statuses' => $statuses])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Créer l'événement</button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
