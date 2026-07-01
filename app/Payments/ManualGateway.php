<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentSettings;
use Illuminate\Http\Request;

/**
 * The default gateway: offline bank transfer. It creates a pending payment and
 * hands the customer the store's bank details; the customer later uploads proof
 * (moving it to `submitted`) and an admin confirms it (PaymentService::confirmManual).
 * There is no webhook - money arrives out of band.
 */
class ManualGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::Manual;
    }

    /** @return list<PaymentMethod> */
    public function supportedMethods(): array
    {
        return [PaymentMethod::BankTransfer];
    }

    public function requiresRedirect(): bool
    {
        return false;
    }

    public function createPayment(Order $order, PaymentMethod $method = PaymentMethod::BankTransfer): PaymentIntent
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => PaymentProvider::Manual,
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => (int) $order->total,
            'currency' => $order->currency,
        ]);

        return new PaymentIntent(
            payment: $payment,
            redirectUrl: null,
            instructions: PaymentSettings::bankDetails(),
        );
    }

    public function handleWebhook(Request $request): PaymentEvent
    {
        throw new \LogicException('The manual gateway has no webhook; payments are confirmed by an admin.');
    }
}
