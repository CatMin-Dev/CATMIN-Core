@extends('frontend.layouts.base')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="d-flex align-items-center justify-content-between mb-5">
                <h1 class="h3 mb-0">Articles</h1>
            </div>

            @if($articles->isEmpty())
                <div class="alert alert-secondary">Aucun article publié pour le moment.</div>
            @else
                <div class="row g-4">
                    @foreach($articles as $article)
                        @php
                            $cardData = [
                                'id'          => $article->id,
                                'title'       => $article->title,
                                'slug'        => $article->slug,
                                'excerpt'     => $article->excerpt ?? '',
                                'published_at'=> $article->published_at,
                                'media_url'   => media_url($article->media_asset_id),
                            ];
                        @endphp
                        <div class="col-12 col-sm-6 col-lg-4">
                            @include('frontend.partials.article-card', ['article' => $cardData])
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($articles->hasPages())
                    <div class="mt-5 d-flex justify-content-center">
                        {{ $articles->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @endif

        </div>
    </div>

</div>
@endsection
