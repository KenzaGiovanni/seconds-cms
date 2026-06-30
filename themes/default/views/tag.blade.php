@extends('theme::layout', ['title' => '#' . $tag->name . ' - ' . config('app.name')])

@section('content')
    <h1>#{{ $tag->name }}</h1>

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
        <p>No posts with this tag.</p>
    @endforelse

    {{ $posts->links() }}
@endsection
