@extends('theme::layout', ['title' => 'Your Cart - ' . config('app.name')])

@section('content')
    <div class="wrap">
        <div class="archive-header">
            <h1>Your Cart</h1>
        </div>

        @livewire('shop.cart-items')
    </div>
@endsection
