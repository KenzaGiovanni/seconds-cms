<?php

namespace App\Livewire\Shop;

use App\Delivery\Address as ShippingAddress;
use App\Delivery\ShipmentService;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Livewire\Concerns\WithRegionPicker;
use App\Support\CartManager;
use App\Support\CheckoutService;
use App\Support\PaymentSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Storefront checkout form, embedded on the theme's checkout page. Guest
 * checkout is allowed - only an email is required, no account.
 */
class Checkout extends Component
{
    use WithRegionPicker;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $addressLine = '';

    public string $postalCode = '';

    public string $notes = '';

    public ?string $deliveryChoice = null;

    public ?string $errorMessage = null;

    public string $couponInput = '';

    public ?string $couponMessage = null;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->email = Auth::user()->email;
            $this->name = Auth::user()->name;
        }
    }

    public function applyCoupon(CartManager $cart): void
    {
        $this->couponMessage = null;

        if (trim($this->couponInput) === '') {
            return;
        }

        if ($cart->applyCoupon($this->couponInput)->hasDiscount()) {
            $this->couponInput = '';
        } else {
            $this->couponMessage = 'That code is not valid for your cart.';
        }
    }

    public function removeCoupon(CartManager $cart): void
    {
        $cart->removeCoupon();
        $this->couponMessage = null;
    }

    public function placeOrder(CartManager $cart, CheckoutService $checkout, ShipmentService $shipments): void
    {
        $this->errorMessage = null;

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'addressLine' => 'required|string|max:255',
            'provinceCode' => 'required|exists:id_provinces,code',
            'regencyCode' => 'required|exists:id_regencies,code',
            'districtCode' => 'required|exists:id_districts,code',
            'postalCode' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($cart->current()->items()->count() === 0) {
            $this->errorMessage = 'Your cart is empty.';

            return;
        }

        $rates = $shipments->previewRates($this->destinationAddress(), (int) $cart->totals()['subtotal']);
        $chosen = collect($rates)->first(fn ($rate) => $rate->id() === $this->deliveryChoice) ?? ($rates[0] ?? null);

        $district = $this->selectedDistrict();
        $regionNames = $this->selectedRegionNames();

        try {
            $order = $checkout->placeOrder(
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?: null,
                ],
                [
                    'address_line' => $data['addressLine'],
                    'city' => $regionNames['regency'],
                    'postal_code' => $data['postalCode'],
                    'province_code' => $data['provinceCode'],
                    'province_name' => $regionNames['province'],
                    'regency_code' => $data['regencyCode'],
                    'regency_name' => $regionNames['regency'],
                    'district_code' => $data['districtCode'],
                    'district_name' => $regionNames['district'],
                    'subdistrict_id' => $district?->kiriminaja_subdistrict_id,
                ],
                $data['notes'] ?: null,
                $chosen,
            );
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        session(['last_order_number' => $order->number]);

        $this->dispatch('cart-updated');

        $payment = $order->payments()->latest('id')->first();
        $redirectUrl = $payment?->raw_payload['invoice_url'] ?? null;

        if ($redirectUrl) {
            // Xendit hosted checkout - off-site, so no wire:navigate.
            $this->redirect($redirectUrl);

            return;
        }

        $this->redirect(route('order.confirmation', $order->number), navigate: true);
    }

    /** Build the destination Address from whatever the customer has entered so far. */
    private function destinationAddress(): ShippingAddress
    {
        $district = $this->selectedDistrict();
        $regionNames = $this->selectedRegionNames();

        return new ShippingAddress(
            name: $this->name,
            phone: $this->phone,
            address: $this->addressLine,
            subdistrictId: $district?->kiriminaja_subdistrict_id,
            city: $regionNames['regency'],
            postalCode: $this->postalCode ?: null,
        );
    }

    public function render(CartManager $cart, ShipmentService $shipments)
    {
        $provider = PaymentSettings::provider();
        $totals = $cart->totals();

        $rates = $shipments->previewRates($this->destinationAddress(), (int) $totals['subtotal']);

        if ($this->deliveryChoice === null || ! collect($rates)->contains(fn ($rate) => $rate->id() === $this->deliveryChoice)) {
            $this->deliveryChoice = $rates[0]->id() ?? null;
        }

        return view('livewire.shop.checkout', [
            'totals' => $totals,
            'rates' => $rates,
            'paymentProvider' => $provider,
            'paymentMethods' => $provider === PaymentProvider::Xendit
                ? PaymentSettings::xenditEnabledMethods()
                : [PaymentMethod::BankTransfer],
        ]);
    }
}
