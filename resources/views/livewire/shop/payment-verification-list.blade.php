<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Payments</h1>
        <a href="{{ route('admin.shop.payments.settings') }}" wire:navigate
           class="rounded-[var(--radius-btn)] border border-line px-3 py-2 font-display text-xs font-medium text-ink transition hover:bg-soft">
            Payment settings
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-8">
        <h2 class="mb-3 font-display text-sm font-semibold text-ink">Awaiting verification</h2>

        @if ($submitted->isEmpty())
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-8 text-center text-sm text-muted">
                Nothing waiting on review.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($submitted as $payment)
                    <div wire:key="submitted-{{ $payment->id }}" class="rounded-[var(--radius-btn)] border border-line bg-bg p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <a href="{{ route('admin.shop.orders.show', $payment->order->id) }}" wire:navigate class="font-mono text-sm text-ink hover:underline">
                                    {{ $payment->order->number }}
                                </a>
                                <div class="text-xs text-muted">{{ $payment->order->customer_name }} &middot; {{ $payment->order->email }}</div>
                                <div class="mt-1 text-sm font-medium text-ink">{{ $payment->formattedAmount() }}</div>
                                @if ($payment->payer_reference)
                                    <div class="mt-1 text-xs text-muted">Reference: {{ $payment->payer_reference }}</div>
                                @endif
                                <div class="mt-1 text-xs text-muted">Uploaded {{ $payment->proof_uploaded_at?->format('d M Y, H:i') }}</div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.shop.payments.proof', $payment->id) }}" target="_blank"
                                   class="rounded-[var(--radius-btn)] border border-line px-3 py-2 font-display text-xs font-medium text-ink transition hover:bg-soft">
                                    View proof
                                </a>
                                <button type="button" wire:click="confirm({{ $payment->id }})"
                                        wire:confirm="Confirm this payment? The order will be marked paid."
                                        class="rounded-[var(--radius-btn)] bg-accent px-3 py-2 font-display text-xs font-medium text-white transition hover:opacity-90">
                                    Confirm
                                </button>
                                <button type="button" wire:click="startReject({{ $payment->id }})"
                                        class="rounded-[var(--radius-btn)] border border-red-200 px-3 py-2 font-display text-xs font-medium text-red-700 transition hover:bg-red-50">
                                    Reject
                                </button>
                            </div>
                        </div>

                        @if ($rejectingId === $payment->id)
                            <form wire:submit="reject" class="mt-4 space-y-2 border-t border-line pt-4">
                                <label class="block font-display text-xs font-medium text-ink" for="rejectionReason-{{ $payment->id }}">Rejection reason</label>
                                <textarea id="rejectionReason-{{ $payment->id }}" wire:model="rejectionReason" rows="2"
                                          class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                                @error('rejectionReason') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-[var(--radius-btn)] bg-red-600 px-3 py-2 font-display text-xs font-medium text-white transition hover:opacity-90">
                                        Confirm rejection
                                    </button>
                                    <button type="button" wire:click="cancelReject" class="rounded-[var(--radius-btn)] border border-line px-3 py-2 font-display text-xs font-medium text-ink transition hover:bg-soft">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <h2 class="mb-3 font-display text-sm font-semibold text-ink">Pending (no proof yet)</h2>

        @if ($pending->isEmpty())
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-8 text-center text-sm text-muted">
                Nothing pending.
            </div>
        @else
            <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
                <table class="w-full text-sm">
                    <thead class="border-b border-line bg-soft">
                        <tr>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Order</th>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Amount</th>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($pending as $payment)
                            <tr wire:key="pending-{{ $payment->id }}">
                                <td class="px-4 py-3 font-mono text-xs text-ink">{{ $payment->order->number }}</td>
                                <td class="px-4 py-3 text-ink">{{ $payment->formattedAmount() }}</td>
                                <td class="px-4 py-3 text-muted">{{ $payment->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
