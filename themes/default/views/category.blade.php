@extends('theme::layout', ['title' => $category->name . ' - ' . config('app.name')])

@section('content')
    <div class="wrap">
        <div class="archive-header">
            <h1>{{ $category->name }}</h1>
            @if($category->description)
                <p>{{ $category->description }}</p>
            @endif
        </div>

        @forelse($posts as $post)
            <article class="post-card" style="margin-bottom: 1.5rem;">
                <div class="post-card-meta">
                    @if($post->published_at)
                        <time datetime="{{ $post->published_at->toIso8601String() }}">
                            {{ $post->published_at->format('j M Y') }}
                        </time>
                    @endif
                </div>
                <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                @if($post->excerpt)
                    <p>{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <div class="empty-state">
                <p>No posts in this category.</p>
            </div>
        @endforelse

        <div class="pagination-wrap">
            {{ $posts->links() }}
        </div>
    </div>
@endsection
