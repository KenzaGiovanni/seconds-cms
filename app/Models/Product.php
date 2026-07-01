<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockPolicy;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'type', 'name', 'slug', 'status', 'description', 'blocks',
        'sku', 'price', 'currency', 'stock', 'stock_policy', 'featured_image_id',
    ];

    protected $attributes = [
        'currency' => 'IDR',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'status' => ProductStatus::class,
        'stock_policy' => StockPolicy::class,
        'blocks' => 'array',
        'price' => 'integer',
        'stock' => 'integer',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_product');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    public function isSimple(): bool
    {
        return $this->type === ProductType::Simple;
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
    }

    /** Resolve the effective stock level for the product or a given variant. */
    public function availableStock(?ProductVariant $variant = null): int
    {
        return (int) ($variant ? $variant->stock : ($this->stock ?? 0));
    }

    public function inStock(int $qty = 1, ?ProductVariant $variant = null): bool
    {
        if (! $this->stock_policy->tracksStock()) {
            return true;
        }

        if ($this->stock_policy === StockPolicy::Backorder) {
            return true;
        }

        return $this->availableStock($variant) >= $qty;
    }

    /**
     * Reduce inventory for a purchase. No-op when stock is not tracked; a
     * backorder policy may take stock negative. Persists immediately.
     */
    public function decrementStock(int $qty, ?ProductVariant $variant = null): void
    {
        if (! $this->stock_policy->tracksStock()) {
            return;
        }

        if ($variant) {
            $variant->stock = (int) $variant->stock - $qty;
            $variant->save();

            return;
        }

        $this->stock = (int) ($this->stock ?? 0) - $qty;
        $this->save();
    }

    /** Return inventory (e.g. on order cancel). Mirror of decrementStock. */
    public function incrementStock(int $qty, ?ProductVariant $variant = null): void
    {
        if (! $this->stock_policy->tracksStock()) {
            return;
        }

        if ($variant) {
            $variant->stock = (int) $variant->stock + $qty;
            $variant->save();

            return;
        }

        $this->stock = (int) ($this->stock ?? 0) + $qty;
        $this->save();
    }

    public function formattedPrice(): string
    {
        return Money::format((int) $this->price, $this->currency ?? Money::DEFAULT_CURRENCY);
    }
}
