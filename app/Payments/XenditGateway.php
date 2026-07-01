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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Xendit, called directly via Laravel's Http facade rather than the
 * xendit/xendit-php SDK (no outbound network access to install it when this
 * was built - see seconds-spec.md §14). v1 uses hosted Xendit Invoice to cover
 * VA/QRIS/e-wallet/card with the least integration surface; per-method
 * payment requests can replace this later without touching anything outside
 * this class, since callers only ever see the PaymentGateway interface.
 */
class XenditGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::Xendit;
    }

    /** @return list<PaymentMethod> */
    public function supportedMethods(): array
    {
        return PaymentSettings::xenditEnabledMethods();
    }

    public function requiresRedirect(): bool
    {
        return true;
    }

    public function createPayment(Order $order, PaymentMethod $method): PaymentIntent
    {
        $keys = PaymentSettings::xenditKeys();
        $externalId = $order->number.'-'.Str::random(6);

        $response = Http::withBasicAuth($keys['secret_key'], '')
            ->acceptJson()
            ->post(PaymentSettings::xenditBaseUrl().'/v2/invoices', [
                'external_id' => $externalId,
                'amount' => (int) $order->total,
                'currency' => $order->currency,
                'description' => 'Order '.$order->number,
                'invoice_duration' => PaymentSettings::windowMinutes() * 60,
                'customer' => [
                    'given_names' => $order->customer_name,
                    'email' => $order->email,
                ],
                'success_redirect_url' => route('order.confirmation', $order->number),
                'failure_redirect_url' => route('order.confirmation', $order->number),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Could not create the Xendit payment. Please try again.');
        }

        $invoice = $response->json();

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => PaymentProvider::Xendit,
            'method' => $method,
            'external_id' => $invoice['id'] ?? $externalId,
            'status' => PaymentStatus::Pending,
            'amount' => (int) $order->total,
            'currency' => $order->currency,
            'raw_payload' => $invoice,
        ]);

        return new PaymentIntent(
            payment: $payment,
            redirectUrl: $invoice['invoice_url'] ?? null,
        );
    }

    public function handleWebhook(Request $request): PaymentEvent
    {
        $payload = $request->json()->all();

        return new PaymentEvent(
            externalId: (string) ($payload['id'] ?? ''),
            status: $this->mapStatus((string) ($payload['status'] ?? '')),
            signature: sha1(json_encode($payload)),
            rawPayload: $payload,
        );
    }

    /**
     * Re-query Xendit for a payment's current status - the safety net for a
     * missed webhook (payments:reconcile command, admin "re-check" button).
     */
    public function reconcile(Payment $payment): PaymentEvent
    {
        $keys = PaymentSettings::xenditKeys();

        $response = Http::withBasicAuth($keys['secret_key'], '')
            ->acceptJson()
            ->get(PaymentSettings::xenditBaseUrl().'/v2/invoices/'.$payment->external_id);

        if ($response->failed()) {
            throw new \RuntimeException('Could not reconcile Xendit payment '.$payment->external_id);
        }

        $payload = $response->json();

        return new PaymentEvent(
            externalId: (string) ($payload['id'] ?? $payment->external_id),
            status: $this->mapStatus((string) ($payload['status'] ?? '')),
            signature: sha1(json_encode($payload)),
            rawPayload: $payload,
        );
    }

    private function mapStatus(string $xenditStatus): PaymentStatus
    {
        return match (strtoupper($xenditStatus)) {
            'PAID', 'SETTLED' => PaymentStatus::Paid,
            'EXPIRED' => PaymentStatus::Expired,
            'FAILED' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }
}
