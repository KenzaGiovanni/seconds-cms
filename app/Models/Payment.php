<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment attempt against an order. An order may have several (a failed
 * Xendit attempt then a manual transfer, etc.); the order's paid state derives
 * from whichever attempt settles. See PaymentService for the state machine.
 */
class Payment extends Model
{
    protected $fillable = [
        'order_id', 'gateway', 'method', 'external_id', 'status',
        'amount', 'currency', 'raw_payload', 'paid_at',
        'proof_path', 'proof_uploaded_at', 'payer_reference',
        'verified_by', 'verified_at', 'rejection_reason',
    ];

    protected $attributes = [
        'currency' => Money::DEFAULT_CURRENCY,
        'status' => 'pending',
    ];

    protected $casts = [
        'gateway' => PaymentProvider::class,
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'raw_payload' => 'array',
        'paid_at' => 'datetime',
        'proof_uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function formattedAmount(): string
    {
        return Money::format((int) $this->amount, $this->currency ?? Money::DEFAULT_CURRENCY);
    }
}
