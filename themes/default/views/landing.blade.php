@extends('theme::layout', [
    'title' => $seo['title'] ?? ($content->meta_title ?: $content->title . ' - ' . config('app.name')),
])

@section('content')
    <div class="landing-blocks">
        {!! $renderedBlocks !!}

        @if($content->body)
            <div class="landing-prose">
                <div class="wrap">
                    <div class="article-body">{!! $content->body !!}</div>
                </div>
            </div>
        @endif
    </div>
@endsection
