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
</div>
