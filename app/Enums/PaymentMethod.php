<?php

namespace App\Enums;

/**
 * The concrete instrument a payment uses. `bank_transfer` is the manual mode;
 * the rest are Xendit methods unlocked once Xendit is activated.
 */
enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case VirtualAccount = 'va';
    case Qris = 'qris';
    case EWallet = 'ewallet';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
            self::VirtualAccount => 'Virtual Account',
            self::Qris => 'QRIS',
            self::EWallet => 'E-wallet',
            self::Card => 'Card',
        };
    }

    public function provider(): PaymentProvider
    {
        return $this === self::BankTransfer ? PaymentProvider::Manual : PaymentProvider::Xendit;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $m) => $m->value, self::cases());
    }
}
