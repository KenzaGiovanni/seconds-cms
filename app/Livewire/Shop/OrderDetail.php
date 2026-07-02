<?php

namespace App\Livewire\Shop;

use App\Delivery\KiriminAjaProvider;
use App\Delivery\RateChoice;
use App\Delivery\ShipmentService;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\Permission;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentService;
use App\Payments\XenditGateway;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class OrderDetail extends Component
{
    public Order $order;

    // Manual-shipment fallback: force a manual shipment on this order by hand,
    // regardless of the active delivery provider (spec §4.4).
    public string $manualCourier = '';

    public string $manualServiceName = '';

    public string $manualTrackingNumber = '';

    public $manualCost = 0;

    public function mount(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $this->order = Order::with(['items.product', 'items.variant', 'payments', 'shipments'])->findOrFail($id);
    }

    public function transitionTo(string $status): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $to = OrderStatus::from($status);

        if (! $this->order->canTransitionTo($to)) {
            session()->flash('error', 'That status change is not allowed from here.');

            return;
        }

        // Order::transitionTo handles restock-on-cancel + timestamp stamping.
        $this->order->transitionTo($to);
        session()->flash('success', 'Order status updated.');

        // Customer status-update email (e.g. "paid"/"shipped") is stubbed until mail is configured.
    }

    public function recheckPayment(int $paymentId, XenditGateway $gateway, PaymentService $payments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $payment = Payment::findOrFail($paymentId);
        abort_unless($payment->gateway === PaymentProvider::Xendit, 404);

        try {
            $payments->applyEvent($gateway->reconcile($payment));
            session()->flash('success', 'Payment status re-checked with Xendit.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->order->refresh();
    }

    public function refundPayment(int $paymentId, PaymentService $payments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $payment = Payment::findOrFail($paymentId);
        $payments->markRefunded($payment);

        session()->flash('success', 'Payment marked refunded.');
        $this->order->refresh();
    }

    /**
     * Book the shipment for the courier snapshotted onto the order at
     * checkout (spec §4.2: booking is an explicit paid/admin action, not
     * automatic at checkout - a real courier booking should be a deliberate
     * step). Guarded against double-booking by ShipmentService::book() itself.
     */
    public function bookShipment(ShipmentService $shipments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        if (! in_array($this->order->status, [OrderStatus::Paid, OrderStatus::Fulfilled], true)) {
            session()->flash('error', 'Book the shipment once the order is paid.');

            return;
        }

        if (! $this->order->shipping_courier) {
            session()->flash('error', 'No delivery method was chosen at checkout for this order.');

            return;
        }

        try {
            $shipments->book($this->order, new RateChoice(
                courier: $this->order->shipping_courier,
                serviceCode: $this->order->shipping_service_code ?? '',
                serviceName: $this->order->shipping_service_name ?? $this->order->shipping_courier,
                cost: (int) $this->order->shipping_total,
                currency: $this->order->currency,
            ));
            session()->flash('success', 'Shipment booked.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Could not book the shipment: '.$e->getMessage());
        }

        $this->order->refresh();
    }

    /** Admin advances a shipment's status by hand (manual mode, or a correction). */
    public function advanceShipment(int $shipmentId, string $status, ShipmentService $shipments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $shipment = $this->order->shipments->firstWhere('id', $shipmentId);
        abort_unless($shipment, 404);

        $shipments->advanceManual($shipment, ShipmentStatus::from($status));
        session()->flash('success', 'Shipment status updated.');
        $this->order->refresh();
    }

    /**
     * Manual-shipment fallback (spec §4.4): admin types courier + tracking by
     * hand for offline fulfilment, regardless of the site's active delivery
     * provider. Routes through the same locked ShipmentService::book() path
     * (double-booking guard included) via an explicit provider override.
     */
    public function addManualShipment(ShipmentService $shipments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $data = $this->validate([
            'manualCourier' => 'required|string|max:100',
            'manualServiceName' => 'nullable|string|max:150',
            'manualTrackingNumber' => 'nullable|string|max:100',
            'manualCost' => 'required|integer|min:0',
        ]);

        $shipments->book($this->order, new RateChoice(
            courier: $data['manualCourier'],
            serviceCode: 'manual',
            serviceName: $data['manualServiceName'] ?: $data['manualCourier'],
            cost: (int) $data['manualCost'],
            currency: $this->order->currency,
            trackingNumber: $data['manualTrackingNumber'] ?: null,
        ), ShippingProvider::Manual);

        $this->manualCourier = '';
        $this->manualServiceName = '';
        $this->manualTrackingNumber = '';
        $this->manualCost = 0;

        session()->flash('success', 'Manual shipment added.');
        $this->order->refresh();
    }

    /** Re-check tracking for a live KiriminAja shipment (safety net for a missed webhook). */
    public function recheckShipment(int $shipmentId, KiriminAjaProvider $provider, ShipmentService $shipments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $shipment = $this->order->shipments->firstWhere('id', $shipmentId);
        abort_unless($shipment && $shipment->provider->value === 'kiriminaja', 404);

        $updates = $provider->track($shipment);
        $latest = collect($updates)->last();

        if ($latest) {
            $shipments->advanceManual($shipment, $latest->status);
            session()->flash('success', 'Tracking re-checked.');
        } else {
            session()->flash('error', 'No tracking updates available yet.');
        }

        $this->order->refresh();
    }

    public function render()
    {
        return view('livewire.shop.order-detail', [
            'availableTransitions' => $this->order->status->transitions(),
        ]);
    }

    public function title(): string
    {
        return 'Order '.$this->order->number;
    }
}
