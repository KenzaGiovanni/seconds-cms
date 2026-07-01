<div class="order-payment-panel">
    @if (session('success'))
        <div class="order-payment-notice order-payment-notice--success">{{ session('success') }}</div>
    @endif

    @if (! $payment)
        <p class="order-payment-note">Payment for this order is already settled or no longer open.</p>
    @else
        <div class="order-payment-details">
            <h2 class="checkout-heading">Bank transfer details</h2>
            <p><strong>Bank:</strong> {{ $bankDetails['bank_name'] }}</p>
            <p><strong>Account number:</strong> {{ $bankDetails['account_number'] }}</p>
            <p><strong>Account holder:</strong> {{ $bankDetails['account_holder'] }}</p>
            @if ($bankDetails['instructions'])
                <p>{{ $bankDetails['instructions'] }}</p>
            @endif
            <p><strong>Amount to transfer:</strong> {{ $payment->formattedAmount() }}</p>

            @if ($order->payment_due_at)
                <p class="order-payment-countdown" data-due-at="{{ $order->payment_due_at->toIso8601String() }}">
                    Please pay before {{ $order->payment_due_at->format('d M Y, H:i') }}.
                </p>
            @endif
        </div>

        @if ($payment->status->value === 'submitted')
            <div class="order-payment-notice">
                Your proof of payment was submitted on {{ $payment->proof_uploaded_at?->format('d M Y, H:i') }} and is awaiting review.
                @if ($payment->rejection_reason)
                    <p class="order-payment-notice--error">Previously rejected: {{ $payment->rejection_reason }}. You may re-upload below.</p>
                @endif
            </div>
        @elseif ($payment->rejection_reason)
            <div class="order-payment-notice order-payment-notice--error">
                Your last upload was rejected: {{ $payment->rejection_reason }}. Please re-upload.
            </div>
        @endif

        <form wire:submit="upload" class="order-payment-upload-form">
            <h2 class="checkout-heading">Upload proof of payment</h2>

            <div class="checkout-field">
                <label for="payerReference">Reference / note (optional)</label>
                <input id="payerReference" type="text" wire:model="payerReference" placeholder="e.g. transfer reference number">
                @error('payerReference') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="checkout-field">
                <label for="proof">Proof of payment (image or PDF, max 10MB)</label>
                <input id="proof" type="file" wire:model="proof" accept="image/*,.pdf">
                <div wire:loading wire:target="proof" class="order-payment-note">Uploading...</div>
                @error('proof') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-add-to-cart" wire:loading.attr="disabled" wire:target="upload">
                Submit proof
            </button>
        </form>
    @endif
</div>
