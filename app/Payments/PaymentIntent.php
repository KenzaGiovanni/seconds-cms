<?php

namespace App\Payments;

use App\Models\Payment;

/**
 * The result of a gateway creating a payment: the persisted Payment plus what
 * the storefront should do next. Value object, not a raw SDK type, so the
 * checkout never knows which gateway it is talking to.
 *  - redirectUrl : send the customer here (Xendit hosted checkout).
 *  - instructions: show these inline (manual bank transfer details).
 */
class PaymentIntent
{
    /**
     * @param  array<string, mixed>  $instructions
     */
    public function __construct(
        public readonly Payment $payment,
        public readonly ?string $redirectUrl = null,
        public readonly array $instructions = [],
    ) {}

    public function requiresRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
