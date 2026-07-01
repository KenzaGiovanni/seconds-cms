<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Payments')]
class PaymentVerificationList extends Component
{
    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    /** @var 'submitted'|'pending' */
    public string $tab = 'submitted';

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function confirm(int $paymentId, PaymentService $payments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $payment = Payment::findOrFail($paymentId);

        /** @var User $admin */
        $admin = auth()->user();

        $payments->confirmManual($payment, $admin);

        session()->flash('success', "Payment for order {$payment->order->number} confirmed.");
    }

    public function startReject(int $paymentId): void
    {
        $this->rejectingId = $paymentId;
        $this->rejectionReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->rejectionReason = '';
    }

    public function reject(PaymentService $payments): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $data = $this->validate([
            'rejectionReason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($this->rejectingId);
        $payments->rejectManual($payment, $data['rejectionReason']);

        session()->flash('success', "Payment for order {$payment->order->number} rejected - customer can re-upload.");
        $this->cancelReject();
    }

    public function render()
    {
        return view('livewire.shop.payment-verification-list', [
            'submitted' => Payment::with('order')
                ->where('status', PaymentStatus::Submitted)
                ->latest('proof_uploaded_at')
                ->get(),
            'pending' => Payment::with('order')
                ->where('status', PaymentStatus::Pending)
                ->latest('created_at')
                ->get(),
        ]);
    }
}
