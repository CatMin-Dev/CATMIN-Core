@extends('frontend.layouts.base')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9">

            {{-- Hero / home page content if a published page slug=home exists --}}
            @if($homePage)
                <div class="mb-5">
                    <h1 class="display-5 fw-bold mb-3">{{ $homePage->title }}</h1>
                    @if($homePage->excerpt)
                        <p class="lead text-muted mb-4">{{ $homePage->excerpt }}</p>
                    @endif
                    <div class="cf-article-body">
                        {!! $renderedHome !!}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <h1 class="display-5 fw-bold mb-3">{{ $siteName }}</h1>
                    <p class="lead text-muted mb-4">Bienvenue</p>
                </div>
            @endif

            {{-- Latest articles preview --}}
            @if(!empty($latestArticles) && $latestArticles->isNotEmpty())
                <section class="mt-5" aria-labelledby="cf-latest-heading">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h2 class="h4 mb-0" id="cf-latest-heading">Derniers articles</h2>
                        <a href="{{ route('frontend.articles.index') }}" class="btn btn-outline-secondary btn-sm">
                            Voir tout
                        </a>
                    </div>
                    <div class="row g-4">
                        @foreach($latestArticles as $article)
                            <div class="col-12 col-md-6 col-lg-4">
                                @include('frontend.partials.article-card', ['article' => $article])
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>

</div>
@endsection
