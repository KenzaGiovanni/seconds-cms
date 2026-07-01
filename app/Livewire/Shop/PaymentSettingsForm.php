<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\Setting;
use App\Support\PaymentSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Payment Settings')]
class PaymentSettingsForm extends Component
{
    public string $bankName = '';

    public string $bankAccountNumber = '';

    public string $bankAccountHolder = '';

    public string $bankInstructions = '';

    public $windowMinutes = PaymentSettings::DEFAULT_WINDOW_MINUTES;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $details = PaymentSettings::bankDetails();
        $this->bankName = $details['bank_name'];
        $this->bankAccountNumber = $details['account_number'];
        $this->bankAccountHolder = $details['account_holder'];
        $this->bankInstructions = $details['instructions'];
        $this->windowMinutes = PaymentSettings::windowMinutes();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $data = $this->validate([
            'bankName' => 'required|string|max:255',
            'bankAccountNumber' => 'required|string|max:100',
            'bankAccountHolder' => 'required|string|max:255',
            'bankInstructions' => 'nullable|string|max:2000',
            'windowMinutes' => 'required|integer|min:1|max:1440',
        ]);

        Setting::set('bank_name', $data['bankName']);
        Setting::set('bank_account_number', $data['bankAccountNumber']);
        Setting::set('bank_account_holder', $data['bankAccountHolder']);
        Setting::set('bank_instructions', $data['bankInstructions'] ?? '');
        Setting::set('payment_window_minutes', (string) $data['windowMinutes']);

        session()->flash('success', 'Payment settings saved.');
    }

    public function render()
    {
        return view('livewire.shop.payment-settings-form');
    }
}
