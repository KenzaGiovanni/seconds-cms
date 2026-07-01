<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'number', 'status', 'user_id', 'email', 'customer_name', 'phone',
        'shipping_address', 'billing_address',
        'subtotal', 'shipping_total', 'discount_total', 'total', 'currency', 'notes',
        'promotion_id', 'coupon_id', 'coupon_code', 'discount_units',
        'placed_at', 'paid_at', 'fulfilled_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'integer',
        'shipping_total' => 'integer',
        'discount_total' => 'integer',
        'total' => 'integer',
        'discount_units' => 'integer',
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->number ??= static::generateNumber();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Move the order to a new status, enforcing the OrderStatus state machine.
     * Stamps the matching timestamp. Throws on an illegal transition.
     */
    public function transitionTo(OrderStatus $to): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw InvalidOrderTransition::between($this->status, $to);
        }

        // Release reserved stock when cancelling from a state that still held a
        // reservation (awaiting_payment / paid). Checkout decrements stock up
        // front, so cancelling must give it back. Centralised here so every
        // caller - admin action, payment webhooks (Phase 3), expiry jobs - gets
        // it for free. `shouldRestockOnCancel()` reflects the state being LEFT.
        if ($to === OrderStatus::Cancelled && $this->status->shouldRestockOnCancel()) {
            $this->restockItems();
            $this->releasePromotion();
        }

        $this->status = $to;

        $stamp = match ($to) {
            OrderStatus::Paid => 'paid_at',
            OrderStatus::Fulfilled => 'fulfilled_at',
            OrderStatus::Completed => 'completed_at',
            OrderStatus::Cancelled => 'cancelled_at',
            default => null,
        };

        if ($stamp) {
            $this->{$stamp} = now();
        }

        $this->save();
    }

    /** Return each line's quantity to the product/variant it was reserved from. */
    protected function restockItems(): void
    {
        $this->loadMissing('items.product', 'items.variant');

        foreach ($this->items as $item) {
            $item->product?->incrementStock($item->quantity, $item->variant);
        }
    }

    /** Give back the promotion quota + coupon use this order consumed. */
    protected function releasePromotion(): void
    {
        if ($this->promotion_id && $this->discount_units > 0) {
            $promo = Promotion::find($this->promotion_id);
            if ($promo) {
                $promo->usage_count = max(0, $promo->usage_count - $this->discount_units);
                $promo->save();
            }
        }

        if ($this->coupon_id) {
            $coupon = Coupon::find($this->coupon_id);
            if ($coupon) {
                $coupon->used_count = max(0, $coupon->used_count - 1);
                $coupon->save();
            }
        }
    }

    public function canTransitionTo(OrderStatus $to): bool
    {
        return $this->status->canTransitionTo($to);
    }

    /** Recalculate money totals from line items (excludes shipping/discount wiring, added at checkout). */
    public function recalculateTotals(): void
    {
        $this->subtotal = (int) $this->items->sum('line_total');
        $this->discount_total = (int) $this->discount_total;
        $this->shipping_total = (int) $this->shipping_total;
        $this->total = $this->subtotal + $this->shipping_total - $this->discount_total;
    }

    public function formattedTotal(): string
    {
        return Money::format((int) $this->total, $this->currency ?? Money::DEFAULT_CURRENCY);
    }

    public static function generateNumber(): string
    {
        return 'SEC-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}
