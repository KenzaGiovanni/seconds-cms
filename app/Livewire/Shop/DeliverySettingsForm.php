<?php

namespace App\Livewire\Shop;

use App\Delivery\Address;
use App\Delivery\KiriminAjaClient;
use App\Enums\Permission;
use App\Enums\ShippingProvider;
use App\Models\Setting;
use App\Support\DeliverySettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Delivery Settings')]
class DeliverySettingsForm extends Component
{
    // Provider-agnostic: origin address + parcel defaults + flat-rate fallback.
    public string $originName = '';

    public string $originPhone = '';

    public string $originAddress = '';

    public string $originCity = '';

    public string $originPostal = '';

    public $originSubdistrictId = '';

    public $defaultWeight = DeliverySettings::DEFAULT_WEIGHT_GRAMS;

    public $flatRate = DeliverySettings::DEFAULT_FLAT_RATE;

    // KiriminAja activation - blank means "keep the existing stored value".
    public string $kiriminajaApiKey = '';

    public string $kiriminajaMode = 'staging';

    public string $kiriminajaWebhookToken = '';

    public string $enabledCouriers = '';

    /** Which integration card's config panel is expanded - defaults to whichever is active. */
    public string $activeProvider = 'manual';

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $origin = DeliverySettings::origin();
        $this->originName = $origin->name;
        $this->originPhone = $origin->phone;
        $this->originAddress = $origin->address;
        $this->originCity = (string) $origin->city;
        $this->originPostal = (string) $origin->postalCode;
        $this->originSubdistrictId = $origin->subdistrictId ?? '';

        $this->defaultWeight = DeliverySettings::defaultWeightGrams();
        $this->flatRate = DeliverySettings::flatRate();
        $this->enabledCouriers = implode(', ', DeliverySettings::enabledCouriers());
        $this->activeProvider = DeliverySettings::provider()->value;
    }

    public function selectProvider(string $provider): void
    {
        $this->activeProvider = $provider;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $data = $this->validate([
            'originName' => 'required|string|max:255',
            'originPhone' => 'required|string|max:20',
            'originAddress' => 'required|string|max:255',
            'originCity' => 'nullable|string|max:120',
            'originPostal' => 'nullable|string|max:20',
            'originSubdistrictId' => 'nullable|integer',
            'defaultWeight' => 'required|integer|min:1',
            'flatRate' => 'required|integer|min:0',
        ]);

        DeliverySettings::setOrigin(new Address(
            name: $data['originName'],
            phone: $data['originPhone'],
            address: $data['originAddress'],
            subdistrictId: $data['originSubdistrictId'] !== '' && $data['originSubdistrictId'] !== null ? (int) $data['originSubdistrictId'] : null,
            city: $data['originCity'],
            postalCode: $data['originPostal'],
        ));
        Setting::set('delivery_default_weight', (string) $data['defaultWeight']);
        Setting::set('delivery_flat_rate', (string) $data['flatRate']);

        session()->flash('success', 'Delivery settings saved.');
    }

    public function activateKiriminaja(KiriminAjaClient $client): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        $data = $this->validate([
            'kiriminajaApiKey' => 'nullable|string|max:255',
            'kiriminajaMode' => 'required|in:staging,production',
            'kiriminajaWebhookToken' => 'nullable|string|max:255',
            'enabledCouriers' => 'nullable|string|max:500',
        ]);

        $existing = DeliverySettings::kiriminajaKeys();
        $apiKey = $data['kiriminajaApiKey'] ?: $existing['api_key'];
        $webhookToken = $data['kiriminajaWebhookToken'] ?: $existing['webhook_token'];

        if ($apiKey === '') {
            $this->addError('kiriminajaApiKey', 'Enter a KiriminAja API key to activate.');

            return;
        }

        $client->configure($apiKey, $data['kiriminajaMode']);

        try {
            $client->creditBalance();
        } catch (\RuntimeException $e) {
            session()->flash('error', 'Could not verify that KiriminAja key - check it and try again.');

            return;
        }

        DeliverySettings::setKiriminajaKeys($apiKey, $data['kiriminajaMode'], $webhookToken);
        $couriers = array_values(array_filter(array_map('trim', explode(',', $data['enabledCouriers'] ?? ''))));
        DeliverySettings::setEnabledCouriers($couriers);
        DeliverySettings::setProvider(ShippingProvider::Kiriminaja);

        $this->kiriminajaApiKey = '';
        $this->kiriminajaWebhookToken = '';

        session()->flash('success', 'KiriminAja activated - it is now the active delivery provider.');
    }

    public function useManualDelivery(): void
    {
        abort_unless(auth()->user()->can(Permission::OrdersManage->value), 403);

        DeliverySettings::setProvider(ShippingProvider::Manual);

        session()->flash('success', 'Switched back to manual delivery.');
    }

    public function render()
    {
        return view('livewire.shop.delivery-settings-form', [
            'provider' => DeliverySettings::provider(),
            'maskedApiKey' => DeliverySettings::maskedKiriminajaApiKey(),
        ]);
    }
}
