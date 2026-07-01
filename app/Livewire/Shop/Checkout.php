<?php

namespace App\Livewire\Shop;

use App\Support\CartManager;
use App\Support\CheckoutService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Storefront checkout form, embedded on the theme's checkout page. Guest
 * checkout is allowed - only an email is required, no account.
 */
class Checkout extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $addressLine = '';

    public string $city = '';

    public string $postalCode = '';

    public string $notes = '';

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

    public function placeOrder(CartManager $cart, CheckoutService $checkout): void
    {
        $this->errorMessage = null;

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'addressLine' => 'required|string|max:255',
            'city' => 'required|string|max:120',
            'postalCode' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($cart->current()->items()->count() === 0) {
            $this->errorMessage = 'Your cart is empty.';

            return;
        }

        try {
            $order = $checkout->placeOrder(
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?: null,
                ],
                [
                    'address_line' => $data['addressLine'],
                    'city' => $data['city'],
                    'postal_code' => $data['postalCode'],
                ],
                $data['notes'] ?: null,
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

    public function render(CartManager $cart)
    {
        return view('livewire.shop.checkout', [
            'totals' => $cart->totals(),
        ]);
    }
}
