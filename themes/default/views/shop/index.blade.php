@extends('theme::layout', ['title' => 'Shop - ' . config('app.name')])

@section('content')
    <div class="wrap">
        <div class="archive-header">
            <h1>Shop</h1>
        </div>

        {{-- Category filter --}}
        @if ($categories->isNotEmpty())
            <div class="shop-filters">
                <a href="{{ route('shop.index') }}"
                   class="{{ $activeCategory === null ? 'pill active' : 'pill' }}">All</a>
                @foreach ($categories as $cat)
                    <a href="{{ route('shop.index', ['category' => $cat->slug]) }}"
                       class="{{ $activeCategory?->id === $cat->id ? 'pill active' : 'pill' }}">{{ $cat->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="shop-grid">
        @forelse ($products as $product)
            <a href="{{ route('shop.product', $product->slug) }}" class="product-card">
                @if ($product->featuredImage)
                    <div class="product-card-img">
                        <img src="{{ $product->featuredImage->url() }}" alt="{{ $product->featuredImage->alt ?? $product->name }}">
                    </div>
                @else
                    <div class="product-card-img product-card-img--empty"></div>
                @endif
                <div class="product-card-body">
                    <h2 class="product-card-name">{{ $product->name }}</h2>
                    @if ($product->categories->isNotEmpty())
                        <p class="product-card-cats">{{ $product->categories->pluck('name')->implode(', ') }}</p>
                    @endif
                    <p class="product-card-price">
                        @if ($product->isSimple())
                            {{ $product->formattedPrice() }}
                        @else
                            From {{ $product->variants->min('price') ? \App\Support\Money::format((int) $product->variants->min('price')) : '-' }}
                        @endif
                    </p>
                    @if ($product->stock_policy->value !== 'none')
                        @php $stock = $product->isSimple() ? ($product->stock ?? 0) : $product->variants->sum('stock'); @endphp
                        @if ($stock <= 0 && $product->stock_policy->value === 'deny')
                            <span class="badge badge--out">Out of stock</span>
                        @elseif ($stock > 0 && $stock <= 5)
                            <span class="badge badge--low">Only {{ $stock }} left</span>
                        @endif
                    @endif
                </div>
            </a>
        @empty
            <div class="empty-state">
                <p>No products available.</p>
            </div>
        @endforelse
        </div>
    </div>
@endsection
