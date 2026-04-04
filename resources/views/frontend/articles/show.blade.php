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
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.articles.index') }}">Articles</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $article->title }}</li>
                </ol>
            </nav>

            <article class="cf-card p-4 p-lg-5">

                {{-- Cover image --}}
                @php($coverUrl = media_url($article->media_asset_id))
                @if($coverUrl)
                    <img src="{{ $coverUrl }}"
                         alt="{{ e($article->title) }}"
                         class="img-fluid rounded mb-4"
                         style="max-height:400px;width:100%;object-fit:cover"
                         loading="eager">
                @endif

                <header class="mb-4">
                    <h1 class="h2 mb-2">{{ $article->title }}</h1>

                    <p class="text-muted small mb-0">
                        @if($article->published_at)
                            <time datetime="{{ $article->published_at->toDateString() }}">
                                {{ $article->published_at->translatedFormat('d F Y') }}
                            </time>
                            &nbsp;&middot;&nbsp;
                        @endif
                        {{ $readingTime }} min de lecture

                        @if($article->category)
                            &nbsp;&middot;&nbsp;
                            <span class="badge bg-secondary">{{ $article->category->name }}</span>
                        @endif
                    </p>

                    @if($article->excerpt)
                        <p class="lead text-muted mt-3 mb-0">{{ $article->excerpt }}</p>
                    @endif
                </header>

                <div class="cf-article-body">
                    {!! $renderedContent !!}
                </div>

                @if($article->tags()->exists())
                    <footer class="mt-5 pt-3 border-top">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($article->tags as $tag)
                                <span class="badge text-bg-light border">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </footer>
                @endif

                <div class="mt-5">
                    <a href="{{ route('frontend.articles.index') }}"
                       class="btn btn-outline-secondary btn-sm">
                        &larr; Retour aux articles
                    </a>
                </div>

            </article>

        </div>
    </div>
</div>
@endsection
