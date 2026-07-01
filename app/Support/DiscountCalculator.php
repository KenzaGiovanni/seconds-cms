<?php

namespace App\Support;

use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\Promotion;

/**
 * Computes the discount for a cart. Discounts apply per eligible unit across
 * the whole cart (not product-targeted). For each candidate promotion the
 * number of eligible units is capped by the promo's per-order cap and its
 * remaining global quota; the discount targets the highest-priced units first.
 * Automatic promotions and a valid coupon are all candidates - the single
 * largest discount wins (never stacked).
 */
class DiscountCalculator
{
    /**
     * @param  array<int, array{price: int, qty: int}>  $lines  cart lines: unit price + quantity
     */
    public function calculate(array $lines, ?string $couponCode = null): DiscountResult
    {
        $lines = array_values(array_filter($lines, fn ($l) => $l['qty'] > 0));
        if ($lines === []) {
            return DiscountResult::none();
        }

        $candidates = [];

        // Automatic promotions live right now.
        foreach (Promotion::automatic()->active()->get() as $promo) {
            if ($promo->isActiveNow()) {
                $candidates[] = ['promotion' => $promo, 'coupon' => null];
            }
        }

        // A coupon, if one was entered and it is redeemable + its promo is live.
        if ($couponCode !== null && trim($couponCode) !== '') {
            $coupon = Coupon::findByCode($couponCode);
            if ($coupon && $coupon->hasUsesLeft()) {
                $promo = $coupon->promotion;
                if ($promo && $promo->type === PromotionType::Coupon && $promo->isActiveNow()) {
                    $candidates[] = ['promotion' => $promo, 'coupon' => $coupon];
                }
            }
        }

        $best = DiscountResult::none();

        foreach ($candidates as $candidate) {
            $result = $this->computeFor($candidate['promotion'], $candidate['coupon'], $lines);
            if ($result->discountTotal > $best->discountTotal) {
                $best = $result;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, array{price: int, qty: int}>  $lines
     */
    private function computeFor(Promotion $promo, ?Coupon $coupon, array $lines): DiscountResult
    {
        $totalQty = array_sum(array_column($lines, 'qty'));

        // Minimum-items threshold.
        if ($promo->min_items !== null && $totalQty < $promo->min_items) {
            return DiscountResult::none();
        }

        // How many units may be discounted: capped per order and by global quota.
        $eligible = $totalQty;
        if ($promo->max_discounted_items !== null) {
            $eligible = min($eligible, $promo->max_discounted_items);
        }
        $remaining = $promo->remainingQuota();
        if ($remaining !== null) {
            $eligible = min($eligible, $remaining);
        }
        if ($eligible <= 0) {
            return DiscountResult::none();
        }

        // Discount the highest-priced units first.
        $sorted = $lines;
        usort($sorted, fn ($a, $b) => $b['price'] <=> $a['price']);

        $discount = 0;
        $unitsDiscounted = 0;
        $remainingUnits = $eligible;

        foreach ($sorted as $line) {
            if ($remainingUnits <= 0) {
                break;
            }
            $take = min($remainingUnits, $line['qty']);
            $perUnit = min($line['price'], $promo->discount_type->discountForUnit($line['price'], $promo->discount_value));
            $discount += $perUnit * $take;
            $unitsDiscounted += $take;
            $remainingUnits -= $take;
        }

        if ($discount <= 0) {
            return DiscountResult::none();
        }

        $label = $promo->name.' ('.$promo->discount_type->formatValue($promo->discount_value, $promo->currency).')';

        return new DiscountResult(
            discountTotal: $discount,
            promotion: $promo,
            coupon: $coupon,
            discountUnits: $unitsDiscounted,
            label: $label,
        );
    }
}
