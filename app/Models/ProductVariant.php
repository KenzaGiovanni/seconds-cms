<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'price', 'currency', 'stock', 'options',
    ];

    protected $attributes = [
        'currency' => 'IDR',
    ];

    protected $casts = [
        'options' => 'array',
        'price' => 'integer',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function formattedPrice(): string
    {
        return Money::format((int) $this->price, $this->currency ?? Money::DEFAULT_CURRENCY);
    }

    /** Human label from the options map, e.g. "L / Red". */
    public function label(): string
    {
        return collect($this->options ?? [])
            ->values()
            ->filter()
            ->implode(' / ');
    }
}
