<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.shop.payments.index') }}" wire:navigate class="text-sm text-muted transition hover:text-ink">&larr; Payments</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Payment Settings</h1>
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

    <div class="mb-6 max-w-xl rounded-[var(--radius-btn)] border border-line bg-bg p-5">
        <p class="text-sm text-muted">
            Active provider: <span class="font-medium text-ink">{{ $provider->label() }}</span>
        </p>
    </div>

    @php
        $input = 'w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
        $label = 'mb-1 block font-display text-sm font-medium text-ink';
    @endphp

    <form wire:submit="save" class="max-w-xl space-y-6">
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Manual bank transfer</h2>
            <p class="text-sm text-muted">Shown to the customer at checkout and on their order page while awaiting payment.</p>

            <div>
                <label class="{{ $label }}" for="bankName">Bank name</label>
                <input id="bankName" wire:model="bankName" type="text" class="{{ $input }}" placeholder="Bank Central Asia" />
                @error('bankName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="bankAccountNumber">Account number</label>
                <input id="bankAccountNumber" wire:model="bankAccountNumber" type="text" class="{{ $input }}" />
                @error('bankAccountNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="bankAccountHolder">Account holder</label>
                <input id="bankAccountHolder" wire:model="bankAccountHolder" type="text" class="{{ $input }}" />
                @error('bankAccountHolder') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="bankInstructions">Instructions</label>
                <textarea id="bankInstructions" wire:model="bankInstructions" rows="3" class="{{ $input }}" placeholder="Transfer the exact amount, then upload your proof of payment below."></textarea>
                @error('bankInstructions') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Payment window</h2>

            <div>
                <label class="{{ $label }}" for="windowMinutes">Minutes to pay before an order auto-cancels</label>
                <input id="windowMinutes" wire:model="windowMinutes" type="number" min="1" max="1440" class="{{ $input }}" />
                @error('windowMinutes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-muted">A customer who has uploaded proof of payment is never auto-cancelled while awaiting review.</p>
            </div>
        </div>

        <button type="submit" class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:opacity-90">
            Save
        </button>
    </form>

    <form wire:submit="activateXendit" class="mt-6 max-w-xl space-y-4">
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-sm font-semibold text-ink">Xendit</h2>
                @if ($provider->value === 'xendit')
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                @endif
            </div>
            <p class="text-sm text-muted">Unlocks Virtual Account, QRIS, e-wallet, and card at checkout.</p>

            @if ($maskedSecretKey)
                <p class="text-xs text-muted">Current secret key: <span class="font-mono">{{ $maskedSecretKey }}</span></p>
            @endif

            <div>
                <label class="{{ $label }}" for="xenditSecretKey">Secret key</label>
                <input id="xenditSecretKey" wire:model="xenditSecretKey" type="password" class="{{ $input }}" placeholder="{{ $maskedSecretKey ? 'Leave blank to keep current key' : 'xnd_production_...' }}" />
                @error('xenditSecretKey') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="xenditPublicKey">Public key</label>
                <input id="xenditPublicKey" wire:model="xenditPublicKey" type="text" class="{{ $input }}" placeholder="Leave blank to keep current key" />
            </div>

            <div>
                <label class="{{ $label }}" for="xenditWebhookToken">Webhook verification token</label>
                <input id="xenditWebhookToken" wire:model="xenditWebhookToken" type="password" class="{{ $input }}" placeholder="Leave blank to keep current token" />
            </div>

            <div>
                <label class="{{ $label }}">Enabled methods</label>
                <div class="space-y-2">
                    @foreach ($allXenditMethods as $method)
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input type="checkbox" wire:model="xenditMethods" value="{{ $method->value }}">
                            {{ $method->label() }}
                        </label>
                    @endforeach
                </div>
                @error('xenditMethods') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:opacity-90">
                Activate Xendit
            </button>
            @if ($provider->value === 'xendit')
                <button type="button" wire:click="useManual" wire:confirm="Switch back to manual bank transfer?"
                        class="rounded-[var(--radius-btn)] border border-line px-4 py-2 font-display text-sm font-medium text-ink transition hover:bg-soft">
                    Switch to manual
                </button>
            @endif
        </div>
    </form>
</div>
