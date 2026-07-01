<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.shop.orders.index') }}" wire:navigate class="text-sm text-muted transition hover:text-ink">&larr; Orders</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Order {{ $order->number }}</h1>
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Line items --}}
            <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
                <table class="w-full text-sm">
                    <thead class="border-b border-line bg-soft">
                        <tr>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Item</th>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Qty</th>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Unit price</th>
                            <th class="px-4 py-3 text-left font-display font-medium text-ink">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-ink">{{ $item->name }}</div>
                                    @if ($item->options)
                                        <div class="text-xs text-muted">{{ collect($item->options)->values()->implode(' / ') }}</div>
                                    @endif
                                    @if ($item->sku)
                                        <div class="text-xs text-muted">SKU: {{ $item->sku }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-ink">{{ $item->formattedUnitPrice() }}</td>
                                <td class="px-4 py-3 text-ink">{{ $item->formattedLineTotal() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Shipping / billing --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4">
                <h2 class="mb-2 font-display text-sm font-semibold text-ink">Shipping address</h2>
                <p class="text-sm text-muted">{{ $order->shipping_address['address_line'] ?? '-' }}</p>
                <p class="text-sm text-muted">{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['postal_code'] ?? '' }}</p>
            </div>

            @if ($order->notes)
                <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4">
                    <h2 class="mb-2 font-display text-sm font-semibold text-ink">Notes</h2>
                    <p class="text-sm text-muted">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            {{-- Status + transitions --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-3">
                <h2 class="font-display text-sm font-semibold text-ink">Status</h2>
                <span @class([
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-gray-100 text-gray-600' => $order->status->value === 'pending',
                    'bg-yellow-100 text-yellow-800' => $order->status->value === 'awaiting_payment',
                    'bg-blue-100 text-blue-800' => $order->status->value === 'paid',
                    'bg-purple-100 text-purple-800' => $order->status->value === 'fulfilled',
                    'bg-green-100 text-green-800' => $order->status->value === 'completed',
                    'bg-red-100 text-red-800' => in_array($order->status->value, ['cancelled', 'refunded']),
                ])>
                    {{ $order->status->label() }}
                </span>

                @if (count($availableTransitions))
                    <div class="pt-2 space-y-2">
                        @foreach ($availableTransitions as $to)
                            <button type="button" wire:click="transitionTo('{{ $to->value }}')"
                                    wire:confirm="Move this order to '{{ $to->label() }}'?"
                                    class="w-full rounded-[var(--radius-btn)] border border-line px-3 py-2 font-display text-xs font-medium text-ink transition hover:bg-soft">
                                Mark as {{ $to->label() }}
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-muted">This order is in a final state.</p>
                @endif
            </div>

            {{-- Payments timeline --}}
            @if ($order->payments->isNotEmpty())
                <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                    <h2 class="font-display text-sm font-semibold text-ink">Payments</h2>
                    @foreach ($order->payments->sortByDesc('id') as $payment)
                        <div wire:key="payment-{{ $payment->id }}" class="space-y-1.5 border-b border-line pb-3 text-sm last:border-0 last:pb-0">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="text-ink">{{ $payment->gateway->label() }}</span>
                                    <span class="text-muted">- {{ $payment->status->label() }}</span>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    @if ($payment->gateway->value === 'xendit' && $payment->status->value === 'pending')
                                        <button type="button" wire:click="recheckPayment({{ $payment->id }})"
                                                class="rounded-[var(--radius-btn)] border border-line px-2 py-1 font-display text-xs font-medium text-ink transition hover:bg-soft">
                                            Re-check
                                        </button>
                                    @endif
                                    @if ($payment->status->value === 'paid')
                                        <button type="button" wire:click="refundPayment({{ $payment->id }})"
                                                wire:confirm="Mark this payment refunded? This only records the state - no refund is issued through the gateway yet."
                                                class="rounded-[var(--radius-btn)] border border-red-200 px-2 py-1 font-display text-xs font-medium text-red-700 transition hover:bg-red-50">
                                            Refund
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="text-xs text-muted">{{ $payment->formattedAmount() }} &middot; created {{ $payment->created_at->format('d M Y, H:i') }}</div>
                            @if ($payment->paid_at)
                                <div class="text-xs text-muted">Paid {{ $payment->paid_at->format('d M Y, H:i') }}</div>
                            @endif
                            @if ($payment->proof_uploaded_at)
                                <div class="text-xs text-muted">
                                    Proof uploaded {{ $payment->proof_uploaded_at->format('d M Y, H:i') }}
                                    @if ($payment->payer_reference)
                                        &middot; ref: {{ $payment->payer_reference }}
                                    @endif
                                    <a href="{{ route('admin.shop.payments.proof', $payment->id) }}" target="_blank" class="ml-1 underline">view</a>
                                </div>
                            @endif
                            @if ($payment->verified_at)
                                <div class="text-xs text-muted">Verified by {{ $payment->verifier?->name ?? 'unknown' }} on {{ $payment->verified_at->format('d M Y, H:i') }}</div>
                            @endif
                            @if ($payment->rejection_reason)
                                <div class="text-xs text-red-700">Rejected: {{ $payment->rejection_reason }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Customer --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-2">
                <h2 class="font-display text-sm font-semibold text-ink">Customer</h2>
                <p class="text-sm text-ink">{{ $order->customer_name }}</p>
                <p class="text-sm text-muted">{{ $order->email }}</p>
                @if ($order->phone)
                    <p class="text-sm text-muted">{{ $order->phone }}</p>
                @endif
                <p class="text-xs text-muted">{{ $order->user_id ? 'Registered customer' : 'Guest checkout' }}</p>
            </div>

            {{-- Totals --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-1.5 text-sm">
                <div class="flex justify-between text-muted"><span>Subtotal</span><span>{{ \App\Support\Money::format($order->subtotal, $order->currency) }}</span></div>
                <div class="flex justify-between text-muted"><span>Shipping</span><span>{{ \App\Support\Money::format($order->shipping_total, $order->currency) }}</span></div>
                <div class="flex justify-between text-muted"><span>Discount</span><span>-{{ \App\Support\Money::format($order->discount_total, $order->currency) }}</span></div>
                <div class="flex justify-between border-t border-line pt-1.5 font-semibold text-ink"><span>Total</span><span>{{ $order->formattedTotal() }}</span></div>
            </div>
        </div>
    </div>
</div>
