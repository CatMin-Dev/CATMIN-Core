@extends('frontend.layouts.base')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">

            {{-- Breadcrumb --}}
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <ol class="breadcrumb cf-breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.home') }}">Accueil</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
                </ol>
            </nav>

            <article class="cf-card p-4 p-lg-5">

                <header class="mb-4">
                    <h1 class="h2 mb-2">{{ $page->title }}</h1>
                    @if($page->excerpt)
                        <p class="lead text-muted">{{ $page->excerpt }}</p>
                    @endif
                </header>

                <div class="cf-article-body">
                    {!! $renderedContent !!}
                </div>

                <footer class="mt-5 pt-3 border-top">
                    <a href="{{ route('frontend.home') }}" class="btn btn-outline-secondary btn-sm">
                        &larr; Retour à l'accueil
                    </a>
                </footer>

            </article>

        </div>
    </div>
</div>
@endsection
