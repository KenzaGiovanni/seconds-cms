@extends('theme::layout', [
    'title' => $content->meta_title ?: $content->title,
    'description' => $content->meta_description,
])

@section('content')
    <article>
        <h1>{{ $content->title }}</h1>

        {!! $renderedBlocks !!}

        @if($content->body)
            <div class="body">{!! $content->body !!}</div>
        @endif
    </article>
@endsection
