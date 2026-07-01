@extends('theme::layout', ['title' => 'Order Confirmed - ' . config('app.name')])

@section('content')
    <div class="wrap">
        <div class="archive-header">
            <h1>Thank you, {{ $order->customer_name }}!</h1>
            <p>Your order <strong>{{ $order->number }}</strong> has been placed and is awaiting payment.</p>
        </div>

        <div class="cart-table">
            @foreach ($order->items as $item)
                <div class="cart-row">
                    <div class="cart-row-info">
                        <p class="cart-row-name">{{ $item->name }}</p>
                        @if ($item->options)
                            <p class="cart-row-variant">{{ collect($item->options)->values()->implode(' / ') }}</p>
                        @endif
                        <p class="cart-row-price">{{ $item->formattedUnitPrice() }} &times; {{ $item->quantity }}</p>
                    </div>
                    <div class="cart-row-total">{{ $item->formattedLineTotal() }}</div>
                </div>
            @endforeach
        </div>

        <div class="cart-summary-lines">
            <div class="cart-summary-line">
                <span>Subtotal</span>
                <span>{{ \App\Support\Money::format($order->subtotal, $order->currency) }}</span>
            </div>
            @if ($order->discount_total > 0)
                <div class="cart-summary-line cart-summary-line--discount">
                    <span>Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span>
                    <span>- {{ \App\Support\Money::format($order->discount_total, $order->currency) }}</span>
                </div>
            @endif
        </div>

        <div class="cart-summary">
            <span class="cart-summary-label">Total</span>
            <span class="cart-summary-total">{{ $order->formattedTotal() }}</span>
        </div>

        <div class="order-confirmation-address">
            <h2 class="checkout-heading">Shipping to</h2>
            <p>{{ $order->shipping_address['address_line'] ?? '' }}</p>
            <p>{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['postal_code'] ?? '' }}</p>
        </div>
    </div>
@endsection
