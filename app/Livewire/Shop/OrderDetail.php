<?php

namespace App\Livewire\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\Permission;
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

    public function mount(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $this->order = Order::with(['items.product', 'items.variant', 'payments'])->findOrFail($id);
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
