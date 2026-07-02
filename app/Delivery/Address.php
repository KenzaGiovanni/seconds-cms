<?php

namespace App\Delivery;

/**
 * A shipping endpoint (origin store or customer destination) used for rate
 * calls and booking. Value object, not a raw SDK type, so providers stay
 * swappable. KiriminAja resolves the area via subdistrict_id; the free-text
 * lines are what gets printed on the label.
 */
class Address
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $address,
        public readonly ?int $subdistrictId = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $notes = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'subdistrict_id' => $this->subdistrictId,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'notes' => $this->notes,
        ];
    }
}
