<?php

namespace App\Delivery;

use App\Support\Money;

/**
 * The courier/service the customer chose, passed to DeliveryProvider::book().
 * For manual mode the admin may also supply the tracking number by hand (the
 * provider has no API to return one).
 */
class RateChoice
{
    public function __construct(
        public readonly string $courier,
        public readonly string $serviceCode,
        public readonly string $serviceName,
        public readonly int $cost,
        public readonly string $currency = Money::DEFAULT_CURRENCY,
        public readonly ?string $trackingNumber = null,
    ) {}
}
