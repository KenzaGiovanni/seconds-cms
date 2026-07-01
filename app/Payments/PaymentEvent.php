<?php

namespace App\Payments;

use App\Enums\PaymentStatus;

/**
 * A normalised payment event parsed from a gateway webhook. The signature is
 * used together with (gateway, external_id) to dedupe at-least-once, possibly
 * out-of-order deliveries - see PaymentService::applyEvent().
 */
class PaymentEvent
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly string $externalId,
        public readonly PaymentStatus $status,
        public readonly string $signature,
        public readonly array $rawPayload = [],
    ) {}
}
