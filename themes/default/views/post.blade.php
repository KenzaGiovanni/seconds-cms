@extends('theme::layout', [
    'title' => $content->meta_title ?: $content->title,
    'description' => $content->meta_description,
])

@section('content')
    <article>
        <h1>{{ $content->title }}</h1>

        @if($content->published_at)
            <p class="meta"><time datetime="{{ $content->published_at->toIso8601String() }}">{{ $content->published_at->format('j M Y') }}</time></p>
        @endif

        {!! $renderedBlocks !!}

        @if($content->body)
            <div class="body">{!! $content->body !!}</div>
        @endif
    </article>
@endsection
