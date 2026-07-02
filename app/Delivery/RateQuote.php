<?php

namespace App\Delivery;

use App\Support\Money;

/**
 * One selectable courier/service option returned by DeliveryProvider::rates().
 * The customer picks one at checkout; it is then frozen into a RateChoice and
 * snapshotted onto the order (price locked at purchase, like line items).
 */
class RateQuote
{
    public function __construct(
        public readonly string $courier,       // courier code, e.g. jne
        public readonly string $serviceCode,   // service code, e.g. reg
        public readonly string $serviceName,   // human label, e.g. "JNE Reguler"
        public readonly int $cost,             // integer minor units
        public readonly string $currency = Money::DEFAULT_CURRENCY,
        public readonly ?string $etaText = null, // e.g. "2-3 days"
    ) {}

    public function id(): string
    {
        return $this->courier.':'.$this->serviceCode;
    }

    public function formattedCost(): string
    {
        return Money::format($this->cost, $this->currency);
    }

    /** Freeze this quote into the choice passed to book(). */
    public function toChoice(): RateChoice
    {
        return new RateChoice(
            courier: $this->courier,
            serviceCode: $this->serviceCode,
            serviceName: $this->serviceName,
            cost: $this->cost,
            currency: $this->currency,
        );
    }
}
