<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'name', 'type', 'discount_type', 'discount_value', 'currency', 'active',
        'min_items', 'max_discounted_items', 'usage_quota', 'usage_count',
        'starts_at', 'ends_at', 'days_of_week', 'time_start', 'time_end',
    ];

    protected $attributes = [
        'currency' => 'IDR',
        'usage_count' => 0,
    ];

    protected $casts = [
        'type' => PromotionType::class,
        'discount_type' => DiscountType::class,
        'discount_value' => 'integer',
        'active' => 'boolean',
        'min_items' => 'integer',
        'max_discounted_items' => 'integer',
        'usage_quota' => 'integer',
        'usage_count' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'days_of_week' => 'array',
    ];

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('type', PromotionType::Automatic->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Whether the promotion is live at the given moment: enabled, within its
     * date range, on an allowed weekday, and inside its daily time window.
     */
    public function isActiveNow(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        if (! $this->active) {
            return false;
        }

        if ($this->starts_at && $now->lt($this->starts_at->startOfDay())) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at->endOfDay())) {
            return false;
        }

        $days = $this->days_of_week;
        if (is_array($days) && $days !== [] && ! in_array($now->dayOfWeek, array_map('intval', $days), true)) {
            return false;
        }

        if ($this->time_start && $this->time_end) {
            $current = $now->format('H:i:s');
            $start = $this->normaliseTime($this->time_start);
            $end = $this->normaliseTime($this->time_end);
            if ($current < $start || $current > $end) {
                return false;
            }
        }

        return true;
    }

    /** Discounted units still available under the global quota (null = unlimited). */
    public function remainingQuota(): ?int
    {
        if ($this->usage_quota === null) {
            return null;
        }

        return max(0, $this->usage_quota - $this->usage_count);
    }

    private function normaliseTime(string $time): string
    {
        // Stored as H:i:s or H:i; compare in H:i:s form.
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
