@extends('theme::layout', ['title' => 'Checkout - ' . config('app.name')])

@section('content')
    <div class="wrap">
        <div class="archive-header">
            <h1>Checkout</h1>
        </div>

        @livewire('shop.checkout')
    </div>
@endsection
