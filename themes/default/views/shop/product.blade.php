@extends('theme::layout', ['title' => $seo['title'] ?? ($product->name . ' - ' . config('app.name'))])

@section('content')
    <div class="wrap">
        <div class="product-detail">
            {{-- Featured image --}}
            @if ($product->featuredImage)
                <div class="product-detail-image">
                    <img src="{{ $product->featuredImage->url() }}"
                         alt="{{ $product->featuredImage->alt ?? $product->name }}">
                </div>
            @endif

            {{-- Product info + interactive widget --}}
            <div class="product-detail-info">
                {{-- Breadcrumb --}}
                <p class="product-breadcrumb">
                    <a href="{{ route('shop.index') }}">Shop</a>
                    @foreach ($product->categories as $cat)
                        &rsaquo; <a href="{{ route('shop.index', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
                    @endforeach
                </p>

                <h1 class="product-detail-name">{{ $product->name }}</h1>

                @if ($product->description)
                    <p class="product-detail-desc">{{ $product->description }}</p>
                @endif

                {{-- Livewire variant selector + price + add-to-cart --}}
                @livewire('shop.product-detail', ['productId' => $product->id])

                {{-- Rich content blocks (detailed description) --}}
                @if ($renderedBlocks)
                    <div class="product-detail-content">
                        {!! $renderedBlocks !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
