@extends('theme::layout', ['title' => config('app.name')])

@section('content')
    <h1>Welcome to {{ config('app.name') }}</h1>

    @if($posts->isNotEmpty())
        <h2>Latest posts</h2>
        <ul>
            @foreach($posts as $post)
                <li><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></li>
            @endforeach
        </ul>
    @endif
@endsection
