@extends('theme::layout', ['title' => $category->name . ' - ' . config('app.name')])

@section('content')
    <h1>{{ $category->name }}</h1>

    @if($category->description)
        <p>{{ $category->description }}</p>
    @endif

    @forelse($posts as $post)
        <article>
            <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>

            @if($post->published_at)
                <p class="meta"><time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('j M Y') }}</time></p>
            @endif

            @if($post->excerpt)
                <p>{{ $post->excerpt }}</p>
            @endif
        </article>
    @empty
        <p>No posts in this category.</p>
    @endforelse

    {{ $posts->links() }}
@endsection
