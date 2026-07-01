<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Promotions</h1>
        <a href="{{ route('admin.shop.promotions.create') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            New promotion
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($promotions->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No promotions yet. <a href="{{ route('admin.shop.promotions.create') }}" wire:navigate class="text-accent underline">Create your first promotion.</a>
        </div>
    @else
        <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-soft">
                    <tr>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Name</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Type</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Discount</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Usage</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($promotions as $promo)
                        <tr wire:key="{{ $promo->id }}">
                            <td class="px-4 py-3 font-medium text-ink">{{ $promo->name }}</td>
                            <td class="px-4 py-3 text-muted">
                                {{ $promo->type->label() }}
                                @if ($promo->type->value === 'coupon')
                                    <span class="text-xs">({{ $promo->coupons_count }} codes)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink">{{ $promo->discount_type->formatValue($promo->discount_value, $promo->currency) }}</td>
                            <td class="px-4 py-3 text-muted">
                                @if ($promo->usage_quota !== null)
                                    {{ $promo->usage_count }} / {{ $promo->usage_quota }} units
                                @else
                                    {{ $promo->usage_count }} units
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800' => $promo->isActiveNow(),
                                    'bg-gray-100 text-gray-600' => ! $promo->isActiveNow(),
                                ])>
                                    {{ $promo->isActiveNow() ? 'Live' : ($promo->active ? 'Scheduled' : 'Off') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.shop.promotions.edit', $promo->id) }}"
                                       wire:navigate
                                       class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">Edit</a>
                                    @if ($confirmingDelete === $promo->id)
                                        <span class="text-xs text-muted">Sure?</span>
                                        <button wire:click="delete({{ $promo->id }})" class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Yes, delete</button>
                                        <button wire:click="cancelDelete" class="rounded px-2 py-1 text-xs font-medium text-muted hover:text-ink">Cancel</button>
                                    @else
                                        <button wire:click="confirmDelete({{ $promo->id }})" class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-red-600">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
