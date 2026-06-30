@extends('theme::layout', [
    'title' => $seo['title'] ?? ($content->meta_title ?: $content->title . ' - ' . config('app.name')),
])

@section('content')
    <div class="article-wrap">
        <header class="article-header">
            <h1>{{ $content->title }}</h1>
            <div class="article-meta">
                @if($content->published_at)
                    <time datetime="{{ $content->published_at->toIso8601String() }}">
                        {{ $content->published_at->format('j M Y') }}
                    </time>
                @endif
                @if($content->categories->isNotEmpty())
                    @foreach($content->categories as $cat)
                        <a href="{{ route('category.show', $cat->slug) }}" class="pill">{{ $cat->name }}</a>
                    @endforeach
                @endif
            </div>
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

        @if($content->tags->isNotEmpty())
            <div class="article-tags">
                <span class="label">Tags</span>
                @foreach($content->tags as $tag)
                    <a href="{{ route('tag.show', $tag->slug) }}" class="pill pill-muted">#{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
