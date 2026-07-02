<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.shop.orders.index') }}" wire:navigate class="text-sm text-muted transition hover:text-ink">&larr; Orders</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Delivery Settings</h1>
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

    @php
        $input = 'w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
        $label = 'mb-1 block font-display text-sm font-medium text-ink';
    @endphp

    {{-- General - applies regardless of which provider is active --}}
    <div class="mb-8 max-w-2xl rounded-[var(--radius-btn)] border border-line bg-bg p-5">
        <h2 class="mb-1 font-display text-sm font-semibold text-ink">Origin address &amp; parcel defaults</h2>
        <p class="mb-3 text-sm text-muted">Used as the ship-from address and default parcel weight for rate + booking calls.</p>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="originName">Sender name</label>
                    <input id="originName" wire:model="originName" type="text" class="{{ $input }}" />
                    @error('originName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}" for="originPhone">Sender phone</label>
                    <input id="originPhone" wire:model="originPhone" type="text" class="{{ $input }}" />
                    @error('originPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="{{ $label }}" for="originAddress">Address</label>
                <input id="originAddress" wire:model="originAddress" type="text" class="{{ $input }}" />
                @error('originAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="provinceCode">Province</label>
                    <select id="provinceCode" wire:model.live="provinceCode" class="{{ $input }}">
                        <option value="">Select province</option>
                        @foreach ($this->provinceOptions() as $province)
                            <option value="{{ $province->code }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('provinceCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}" for="regencyCode">City / Regency</label>
                    <select id="regencyCode" wire:model.live="regencyCode" class="{{ $input }}" @disabled(! $provinceCode)>
                        <option value="">Select city / regency</option>
                        @foreach ($this->regencyOptions() as $regency)
                            <option value="{{ $regency->code }}">{{ $regency->name }}</option>
                        @endforeach
                    </select>
                    @error('regencyCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="districtCode">District</label>
                    <select id="districtCode" wire:model.live="districtCode" class="{{ $input }}" @disabled(! $regencyCode)>
                        <option value="">Select district</option>
                        @foreach ($this->districtOptions() as $district)
                            <option value="{{ $district->code }}">{{ $district->name }}{{ $district->kiriminaja_subdistrict_id ? '' : ' (rates: flat-rate until matched)' }}</option>
                        @endforeach
                    </select>
                    @error('districtCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}" for="originPostal">Postal code</label>
                    @php($originPostalOptions = $this->postalCodeOptions())
                    @if ($originPostalOptions->isNotEmpty())
                        <select id="originPostal" wire:model="originPostal" class="{{ $input }}">
                            <option value="">Select postal code</option>
                            @foreach ($originPostalOptions as $option)
                                <option value="{{ $option->postal_code }}">{{ $option->postal_code }} - {{ $option->urban }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="originPostal" wire:model="originPostal" type="text" class="{{ $input }}" placeholder="{{ $districtCode ? 'Not in our postal code data - enter manually' : '' }}" />
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="defaultWeight">Default parcel weight (grams)</label>
                    <input id="defaultWeight" wire:model="defaultWeight" type="number" min="1" class="{{ $input }}" />
                    @error('defaultWeight') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}" for="flatRate">
                        Flat rate{{ $provider->value === 'manual' ? ' (manual delivery)' : ' (fallback when live rates are unavailable)' }}
                    </label>
                    <input id="flatRate" wire:model="flatRate" type="number" min="0" class="{{ $input }}" />
                    @error('flatRate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Manual delivery pricing</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="radio" wire:model.live="manualMode" value="flat">
                        Single flat rate
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="radio" wire:model.live="manualMode" value="free_shipping">
                        Free shipping (with minimum)
                    </label>
                </div>
                @error('manualMode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($manualMode === 'free_shipping')
                <div>
                    <label class="{{ $label }}" for="freeShippingMinimum">Free shipping minimum (cart subtotal)</label>
                    <input id="freeShippingMinimum" wire:model="freeShippingMinimum" type="number" min="0" class="{{ $input }}" placeholder="e.g. 200000" />
                    <p class="mt-1 text-xs text-muted">Orders at or above this subtotal ship free; below it, the flat rate above applies.</p>
                    @error('freeShippingMinimum') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <button type="submit" class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:opacity-90">
                Save
            </button>
        </form>
    </div>

    {{-- Integrations: pick a provider to configure it --}}
    <h2 class="mb-3 font-display text-sm font-semibold text-ink">Delivery providers</h2>
    <div class="mb-6 grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-2">
        <button type="button" wire:click="selectProvider('manual')"
                @class([
                    'rounded-[var(--radius-btn)] border bg-bg p-5 text-left transition',
                    'border-accent ring-1 ring-accent' => $activeProvider === 'manual',
                    'border-line hover:border-accent/50' => $activeProvider !== 'manual',
                ])>
            <div class="flex items-center justify-between">
                <h3 class="font-display text-sm font-semibold text-ink">Manual / offline</h3>
                @if ($provider->value === 'manual')
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Available</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-muted">A single flat-rate option at checkout; you book couriers and enter tracking numbers by hand.</p>
        </button>

        <button type="button" wire:click="selectProvider('kiriminaja')"
                @class([
                    'rounded-[var(--radius-btn)] border bg-bg p-5 text-left transition',
                    'border-accent ring-1 ring-accent' => $activeProvider === 'kiriminaja',
                    'border-line hover:border-accent/50' => $activeProvider !== 'kiriminaja',
                ])>
            <div class="flex items-center justify-between">
                <h3 class="font-display text-sm font-semibold text-ink">KiriminAja</h3>
                @if ($provider->value === 'kiriminaja')
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Available</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-muted">Live courier rates, booking, and tracking. Requires an API key and a resolved origin sub-district.</p>
        </button>
    </div>

    {{-- Selected provider's configuration --}}
    @if ($activeProvider === 'manual')
        <div class="max-w-2xl space-y-2 rounded-[var(--radius-btn)] border border-line bg-bg p-5">
            <h2 class="font-display text-sm font-semibold text-ink">Manual / offline delivery</h2>
            <p class="text-sm text-muted">No configuration needed - checkout shows the flat rate above, and shipments are booked and tracked by hand on each order.</p>
            @if ($provider->value === 'kiriminaja')
                <button type="button" wire:click="useManualDelivery" wire:confirm="Switch back to manual delivery?"
                        class="rounded-[var(--radius-btn)] border border-line px-4 py-2 font-display text-sm font-medium text-ink transition hover:bg-soft">
                    Use manual delivery
                </button>
            @endif
        </div>
    @else
        <form wire:submit="activateKiriminaja" class="max-w-2xl space-y-4 rounded-[var(--radius-btn)] border border-line bg-bg p-5">
            <h2 class="font-display text-sm font-semibold text-ink">Configure KiriminAja</h2>

            @if ($maskedApiKey)
                <p class="text-xs text-muted">Current API key: <span class="font-mono">{{ $maskedApiKey }}</span></p>
            @endif

            <div>
                <label class="{{ $label }}" for="kiriminajaApiKey">API key</label>
                <input id="kiriminajaApiKey" wire:model="kiriminajaApiKey" type="password" class="{{ $input }}" placeholder="{{ $maskedApiKey ? 'Leave blank to keep current key' : '' }}" />
                @error('kiriminajaApiKey') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="kiriminajaMode">Mode</label>
                <select id="kiriminajaMode" wire:model="kiriminajaMode" class="{{ $input }}">
                    <option value="staging">Staging (test)</option>
                    <option value="production">Production</option>
                </select>
            </div>

            <div>
                <label class="{{ $label }}" for="kiriminajaWebhookToken">Webhook verification token</label>
                <input id="kiriminajaWebhookToken" wire:model="kiriminajaWebhookToken" type="password" class="{{ $input }}" placeholder="Leave blank to keep current token" />
            </div>

            <div>
                <label class="{{ $label }}" for="enabledCouriers">Enabled couriers</label>
                <input id="enabledCouriers" wire:model="enabledCouriers" type="text" class="{{ $input }}" placeholder="jne, jnt, sicepat (blank = all couriers)" />
                @error('enabledCouriers') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:opacity-90">
                    Activate KiriminAja
                </button>
                @if ($provider->value === 'kiriminaja')
                    <button type="button" wire:click="useManualDelivery" wire:confirm="Switch back to manual delivery?"
                            class="rounded-[var(--radius-btn)] border border-line px-4 py-2 font-display text-sm font-medium text-ink transition hover:bg-soft">
                        Switch to manual
                    </button>
                @endif
            </div>
        </form>
    @endif
</div>
