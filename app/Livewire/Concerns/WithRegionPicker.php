<?php

namespace App\Livewire\Concerns;

use App\Models\Region\District;
use App\Models\Region\Province;
use App\Models\Region\Regency;
use Illuminate\Support\Collection;

/**
 * Cascading Province -> Regency -> District address picker, backed by the
 * `id_provinces`/`id_regencies`/`id_districts` tables (see
 * `regions:import-indonesia`). Used by both the checkout destination address
 * and the delivery-settings origin address - identical cascading logic in
 * both places, so it's a trait rather than duplicated per component.
 *
 * Selecting a province clears any regency/district picked under a different
 * one; selecting a regency clears any stale district. `selectedDistrict()`
 * is how a host resolves `kiriminaja_subdistrict_id` for a live rate call -
 * null until that district has been reconciled against KiriminAja's own API.
 */
trait WithRegionPicker
{
    public ?string $provinceCode = null;

    public ?string $regencyCode = null;

    public ?string $districtCode = null;

    public function updatedProvinceCode(): void
    {
        $this->regencyCode = null;
        $this->districtCode = null;
    }

    public function updatedRegencyCode(): void
    {
        $this->districtCode = null;
    }

    /** @return Collection<int, Province> */
    public function provinceOptions(): Collection
    {
        return Province::orderBy('name')->get();
    }

    /** @return Collection<int, Regency> */
    public function regencyOptions(): Collection
    {
        if (! $this->provinceCode) {
            return collect();
        }

        return Regency::where('province_code', $this->provinceCode)->orderBy('name')->get();
    }

    /** @return Collection<int, District> */
    public function districtOptions(): Collection
    {
        if (! $this->regencyCode) {
            return collect();
        }

        return District::where('regency_code', $this->regencyCode)->orderBy('name')->get();
    }

    public function selectedDistrict(): ?District
    {
        return $this->districtCode ? District::find($this->districtCode) : null;
    }

    /** Human-readable snapshot for shipping_address / settings - resolved once, not re-queried per render. */
    public function selectedRegionNames(): array
    {
        $district = $this->selectedDistrict();

        return [
            'province' => $district?->regency?->province?->name,
            'regency' => $district?->regency?->name,
            'district' => $district?->name,
        ];
    }
}
