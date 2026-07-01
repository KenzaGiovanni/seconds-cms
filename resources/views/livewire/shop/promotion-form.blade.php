<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.shop.promotions.index') }}" wire:navigate class="text-sm text-muted transition hover:text-ink">&larr; Promotions</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">{{ $editing ? 'Edit Promotion' : 'New Promotion' }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @php
        $input = 'w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
        $label = 'mb-1.5 block font-display text-sm font-medium text-ink';
        $hint = 'mt-1 text-xs text-muted';
    @endphp

    <form wire:submit="save" class="max-w-2xl space-y-5">
        {{-- Basics --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Basics</h2>
            <div>
                <label class="{{ $label }}">Name</label>
                <input wire:model="name" type="text" class="{{ $input }}" placeholder="e.g. Happy Hour 20%">
                @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Type</label>
                    <select wire:model.live="type" class="{{ $input }}">
                        @foreach ($types as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $hint }}">Automatic applies to any qualifying cart; Coupon needs a code.</p>
                </div>
                <div>
                    <label class="{{ $label }}">Discount</label>
                    <div class="flex gap-2">
                        <select wire:model.live="discountType" class="{{ $input }}">
                            @foreach ($discountTypes as $d)
                                <option value="{{ $d->value }}">{{ $d->label() }}</option>
                            @endforeach
                        </select>
                        <input wire:model="discountValue" type="number" min="1" class="{{ $input }}" placeholder="{{ $discountType === 'percentage' ? '20' : '10000' }}">
                    </div>
                    <p class="{{ $hint }}">{{ $discountType === 'percentage' ? 'Percent off each eligible item (1-100).' : 'Rupiah off each eligible item.' }}</p>
                    @error('discountValue') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" wire:model="active" class="rounded border-line text-accent focus:ring-accent">
                <span class="text-sm text-ink">Active</span>
            </label>
        </div>

        {{-- Item rules --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Item rules</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="{{ $label }}">Min items</label>
                    <input wire:model="minItems" type="number" min="1" class="{{ $input }}" placeholder="Any">
                    <p class="{{ $hint }}">Cart must have at least this many items.</p>
                    @error('minItems') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Max discounted / order</label>
                    <input wire:model="maxDiscountedItems" type="number" min="1" class="{{ $input }}" placeholder="No cap">
                    <p class="{{ $hint }}">Extra items beyond this are full price.</p>
                    @error('maxDiscountedItems') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Total quota (units)</label>
                    <input wire:model="usageQuota" type="number" min="1" class="{{ $input }}" placeholder="Unlimited">
                    <p class="{{ $hint }}">Total discounted items across all orders.</p>
                    @error('usageQuota') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Schedule <span class="font-normal text-muted">(all optional)</span></h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Starts</label>
                    <input wire:model="startsAt" type="date" class="{{ $input }}">
                    @error('startsAt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Ends</label>
                    <input wire:model="endsAt" type="date" class="{{ $input }}">
                    @error('endsAt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="{{ $label }}">Days of week</label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($weekdays as $num => $day)
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-[var(--radius-btn)] border border-line px-2.5 py-1.5">
                            <input type="checkbox" wire:model="daysOfWeek" value="{{ $num }}" class="rounded border-line text-accent focus:ring-accent">
                            <span class="text-xs text-ink">{{ $day }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="{{ $hint }}">Leave all unchecked for every day.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">From (time)</label>
                    <input wire:model="timeStart" type="time" class="{{ $input }}">
                    @error('timeStart') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">To (time)</label>
                    <input wire:model="timeEnd" type="time" class="{{ $input }}">
                    <p class="{{ $hint }}">e.g. 16:00 - 20:00 for a happy hour.</p>
                    @error('timeEnd') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="rounded-[var(--radius-btn)] bg-accent px-5 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            {{ $editing ? 'Update promotion' : 'Save promotion' }}
        </button>
    </form>

    {{-- Coupon codes (coupon-type, once saved) --}}
    @if ($type === 'coupon' && $editing)
        <div class="mt-8 max-w-2xl rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-5">
            <h2 class="font-display text-sm font-semibold text-ink">Coupon codes</h2>

            {{-- Add single --}}
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="{{ $label }}">Add a code</label>
                    <input wire:model="newCode" type="text" class="{{ $input }}" placeholder="SAVE20">
                    @error('newCode') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Max uses</label>
                    <input wire:model="newCodeMaxUses" type="number" min="1" class="{{ $input }}" placeholder="∞">
                </div>
                <button type="button" wire:click="addCoupon" class="rounded-[var(--radius-btn)] border border-line px-4 py-2 font-display text-sm font-medium text-ink transition hover:bg-soft">Add</button>
            </div>

            {{-- Mass generate --}}
            <div class="rounded-[var(--radius-btn)] border border-dashed border-line p-4">
                <p class="mb-3 font-display text-xs font-semibold uppercase tracking-wide text-muted">Mass generate</p>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="{{ $label }}">How many</label>
                        <input wire:model="genCount" type="number" min="1" max="1000" class="{{ $input }} w-24">
                        @error('genCount') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Prefix</label>
                        <input wire:model="genPrefix" type="text" class="{{ $input }} w-32" placeholder="RAYA-">
                        @error('genPrefix') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Max uses each</label>
                        <input wire:model="genMaxUses" type="number" min="1" class="{{ $input }} w-24" placeholder="1">
                    </div>
                    <button type="button" wire:click="generateCoupons" class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">Generate</button>
                </div>
            </div>

            {{-- Existing codes --}}
            @if ($coupons->isNotEmpty())
                <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line">
                    <table class="w-full text-sm">
                        <thead class="border-b border-line bg-soft">
                            <tr>
                                <th class="px-3 py-2 text-left font-display font-medium text-ink">Code</th>
                                <th class="px-3 py-2 text-left font-display font-medium text-ink">Uses</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($coupons as $coupon)
                                <tr wire:key="coupon-{{ $coupon->id }}">
                                    <td class="px-3 py-2 font-mono text-xs text-ink">{{ $coupon->code }}</td>
                                    <td class="px-3 py-2 text-muted">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <button wire:click="deleteCoupon({{ $coupon->id }})" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="{{ $hint }}">{{ $coupons->count() }} code(s).</p>
            @else
                <p class="text-xs text-muted">No codes yet. Add one or mass-generate a batch.</p>
            @endif
        </div>
    @elseif ($type === 'coupon' && ! $editing)
        <p class="mt-4 max-w-2xl text-sm text-muted">Save the promotion first, then add or generate coupon codes.</p>
    @endif
</div>
