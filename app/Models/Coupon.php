<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = ['promotion_id', 'code', 'max_uses', 'used_count'];

    protected $attributes = [
        'used_count' => 0,
    ];

    protected $casts = [
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** Whether this code still has redemptions left (max_uses null = unlimited). */
    public function hasUsesLeft(): bool
    {
        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    public static function findByCode(string $code): ?self
    {
        return static::whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->first();
    }
}
