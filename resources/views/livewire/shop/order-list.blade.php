<div>
    <h1 class="mb-6 font-display text-2xl font-semibold tracking-tight text-ink">Orders</h1>

    @if ($orders->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No orders yet.
        </div>
    @else
        <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-soft">
                    <tr>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Order</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Customer</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Items</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Total</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Status</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Placed</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($orders as $order)
                        <tr wire:key="{{ $order->id }}">
                            <td class="px-4 py-3 font-mono text-xs text-ink">{{ $order->number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink">{{ $order->customer_name }}</div>
                                <div class="text-xs text-muted">{{ $order->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $order->items_count }}</td>
                            <td class="px-4 py-3 text-ink">{{ $order->formattedTotal() }}</td>
                            <td class="px-4 py-3">
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
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $order->placed_at?->format('d M Y, H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.shop.orders.show', $order->id) }}"
                                   wire:navigate
                                   class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
