@extends('theme::layout', [
    'title' => $seo['title'] ?? ($content->meta_title ?: $content->title . ' - ' . config('app.name')),
])

@section('content')
    <div class="article-wrap">
        <header class="article-header">
            <h1>{{ $content->title }}</h1>
        </header>

        @if($content->featuredImage)
            <div class="article-featured-image">
                <img src="{{ $content->featuredImage->url() }}"
                     alt="{{ $content->featuredImage->alt ?: $content->title }}">
            </div>
        @endif

        <div class="article-body">
            {!! $renderedBlocks !!}
            @if($content->body)
                {!! $content->body !!}
            @endif
        </div>
    </div>
@endsection
