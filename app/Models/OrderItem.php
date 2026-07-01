<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'name', 'sku', 'unit_price', 'quantity', 'line_total', 'currency', 'options',
    ];

    protected $casts = [
        'options' => 'array',
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'line_total' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function formattedUnitPrice(): string
    {
        return Money::format((int) $this->unit_price, $this->currency ?? Money::DEFAULT_CURRENCY);
    }

    public function formattedLineTotal(): string
    {
        return Money::format((int) $this->line_total, $this->currency ?? Money::DEFAULT_CURRENCY);
    }
}
