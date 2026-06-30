@extends('theme::layout', ['title' => 'Blog - ' . config('app.name')])

@section('content')
    <h1>Blog</h1>

    @forelse($posts as $post)
        <article>
            <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>

            @if($post->published_at)
                <p class="meta"><time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('j M Y') }}</time></p>
            @endif

            @if($post->categories->isNotEmpty())
                <p class="categories">
                    @foreach($post->categories as $cat)
                        <a href="{{ route('category.show', $cat->slug) }}">{{ $cat->name }}</a>@if(!$loop->last), @endif
                    @endforeach
                </p>
            @endif

            @if($post->excerpt)
                <p>{{ $post->excerpt }}</p>
            @endif
        </article>
    @empty
        <p>No posts yet.</p>
    @endforelse

    {{ $posts->links() }}
@endsection
