<?php

namespace App\Livewire\Shop;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\PaymentService;
use App\Support\PaymentSettings;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Proof-of-payment upload, embedded on the order confirmation/pending page.
 * Only the order owner (same ownership rule as FrontController::orderConfirmation
 * - auth match or the post-checkout session flag) may upload; the check is
 * re-applied here since this is a separate Livewire request lifecycle.
 */
class ProofUpload extends Component
{
    use WithFileUploads;

    public Order $order;

    /** @var TemporaryUploadedFile|null */
    public $proof = null;

    public string $payerReference = '';

    public function mount(Order $order): void
    {
        abort_unless($this->isOwner($order), 404);

        $this->order = $order;
    }

    public function upload(PaymentService $payments): void
    {
        abort_unless($this->isOwner($this->order), 404);

        $payment = $this->currentPayment();
        abort_unless($payment && $payment->status->isOpen(), 404);

        $this->validate([
            'proof' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf',
            'payerReference' => 'nullable|string|max:255',
        ]);

        $path = $this->proof->storeAs(
            'proofs/'.$this->order->id,
            Str::random(20).'.'.$this->proof->getClientOriginalExtension(),
            'local',
        );

        $payments->submitProof($payment, $path, $this->payerReference ?: null);

        $this->reset('proof');
        session()->flash('success', 'Proof of payment uploaded - our team will review it shortly.');
    }

    private function isOwner(Order $order): bool
    {
        return ($order->user_id !== null && $order->user_id === auth()->id())
            || session('last_order_number') === $order->number;
    }

    private function currentPayment()
    {
        return $this->order->payments()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Submitted->value])
            ->latest('id')
            ->first();
    }

    public function render()
    {
        return view('livewire.shop.proof-upload', [
            'payment' => $this->currentPayment(),
            'bankDetails' => PaymentSettings::bankDetails(),
        ]);
    }
}
