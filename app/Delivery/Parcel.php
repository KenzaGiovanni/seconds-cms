<?php

namespace App\Delivery;

/**
 * The physical parcel being shipped: total weight (grams) + optional dimensions
 * (cm) + declared item value (integer minor units) for insurance/COD. Defaults
 * come from Shop settings when a real per-order calculation is not available.
 */
class Parcel
{
    public function __construct(
        public readonly int $weightGrams,
        public readonly int $itemValue = 0,
        public readonly ?int $lengthCm = null,
        public readonly ?int $widthCm = null,
        public readonly ?int $heightCm = null,
    ) {}
}
