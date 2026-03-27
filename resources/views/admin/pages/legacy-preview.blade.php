@extends('admin.layouts.catmin')

@section('page_title', 'Legacy Preview')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Legacy preview</h1>
    <p class="text-muted mb-0">Rendu legacy isole dans le shell admin.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-body">
            @if(!empty($legacyContent))
                {!! $legacyContent !!}
            @else
                <div class="alert alert-secondary mb-0" role="alert">Aucun contenu legacy n'est disponible.</div>
            @endif
        </div>
    </div>
</div>
@endsection
