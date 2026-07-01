@extends('theme::layout', ['title' => config('app.name')])

@section('content')

    {{-- Blog-feed home. A static front page (set in Website Settings) renders via
         the landing template instead; this is the "latest posts" fallback. --}}
    <section class="archive-header">
        <div class="wrap">
            <h1>{{ config('app.name') }}</h1>
            @if(!empty($themeSettings['footer_text']))
                <p>{{ $themeSettings['footer_text'] }}</p>
            @endif
        </div>
    </section>

    {{-- Latest posts --}}
    @if($posts->isNotEmpty())
        <section class="section">
            <div class="wrap">
                <div class="section-header">
                    <h2>Latest</h2>
                    <a href="{{ route('blog.index') }}" class="section-link">All posts &rarr;</a>
                </div>

                <div class="post-grid">
                    @foreach($posts as $post)
                        <article class="post-card">
                            <div class="post-card-meta">
                                @if($post->published_at)
                                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                                        {{ $post->published_at->format('j M Y') }}
                                    </time>
                                @endif
                                @if($post->categories->isNotEmpty())
                                    @foreach($post->categories as $cat)
                                        <a href="{{ route('category.show', $cat->slug) }}" class="pill">{{ $cat->name }}</a>
                                    @endforeach
                                @endif
                            </div>
                            <h3><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h3>
                            @if($post->excerpt)
                                <p>{{ $post->excerpt }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <div class="section">
            <div class="wrap">
                <p style="color: var(--muted);">Nothing published yet.</p>
            </div>
        </div>
    @endif

@endsection
