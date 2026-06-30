@extends('theme::layout', ['title' => 'Blog - ' . config('app.name')])

@section('content')
    <div class="wrap">
        <div class="archive-header">
            <h1>Blog</h1>
        </div>

        @forelse($posts as $post)
            <article class="post-card" style="margin-bottom: 1.5rem;">
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
                <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                @if($post->excerpt)
                    <p>{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <div class="empty-state">
                <p>No posts published yet.</p>
            </div>
        @endforelse

        <div class="pagination-wrap">
            {{ $posts->links() }}
        </div>
    </div>
@endsection
