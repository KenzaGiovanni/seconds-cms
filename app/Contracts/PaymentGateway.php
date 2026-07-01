<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Models\Order;
use App\Payments\PaymentEvent;
use App\Payments\PaymentIntent;
use Illuminate\Http\Request;

/**
 * The one contract every payment mode implements (ManualGateway, XenditGateway).
 * Returns value objects, never raw SDK types, so checkout + the order state
 * machine stay gateway-agnostic. Locked in the 3.0 Opus spike - keep it stable.
 */
interface PaymentGateway
{
    public function provider(): PaymentProvider;

    /** @return list<PaymentMethod> Methods this gateway can currently offer. */
    public function supportedMethods(): array;

    /** True if paying means redirecting the customer off-site (Xendit hosted). */
    public function requiresRedirect(): bool;

    /**
     * Create a payment attempt for the order and persist a `payments` row
     * (status pending). Does not itself move the order - that happens when the
     * payment settles via PaymentService.
     */
    public function createPayment(Order $order, PaymentMethod $method): PaymentIntent;

    /**
     * Parse + verify an inbound webhook into a normalised event. Throws if the
     * request is unverified/unparseable. Manual has no webhook and throws.
     */
    public function handleWebhook(Request $request): PaymentEvent;
}
