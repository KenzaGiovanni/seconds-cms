<?php

namespace App\Livewire\Shop;

use App\Enums\DiscountType;
use App\Enums\Permission;
use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PromotionForm extends Component
{
    public ?int $promotionId = null;

    public string $name = '';

    public string $type = 'automatic';

    public string $discountType = 'percentage';

    public $discountValue = '';

    public bool $active = true;

    public $minItems = '';

    public $maxDiscountedItems = '';

    public $usageQuota = '';

    public string $startsAt = '';

    public string $endsAt = '';

    /** @var array<int, int> selected weekdays (0=Sun..6=Sat) */
    public array $daysOfWeek = [];

    public string $timeStart = '';

    public string $timeEnd = '';

    // Coupon management (coupon-type only).
    public string $newCode = '';

    public $newCodeMaxUses = '';

    public int $genCount = 10;

    public string $genPrefix = '';

    public $genMaxUses = '';

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);

        if ($id) {
            $p = Promotion::findOrFail($id);
            $this->promotionId = $p->id;
            $this->name = $p->name;
            $this->type = $p->type->value;
            $this->discountType = $p->discount_type->value;
            $this->discountValue = $p->discount_value;
            $this->active = $p->active;
            $this->minItems = $p->min_items ?? '';
            $this->maxDiscountedItems = $p->max_discounted_items ?? '';
            $this->usageQuota = $p->usage_quota ?? '';
            $this->startsAt = $p->starts_at?->toDateString() ?? '';
            $this->endsAt = $p->ends_at?->toDateString() ?? '';
            $this->daysOfWeek = array_map('intval', $p->days_of_week ?? []);
            $this->timeStart = $p->time_start ? substr($p->time_start, 0, 5) : '';
            $this->timeEnd = $p->time_end ? substr($p->time_end, 0, 5) : '';
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', PromotionType::values()),
            'discountType' => 'required|in:'.implode(',', DiscountType::values()),
            'discountValue' => 'required|integer|min:1'.($this->discountType === 'percentage' ? '|max:100' : ''),
            'minItems' => 'nullable|integer|min:1',
            'maxDiscountedItems' => 'nullable|integer|min:1',
            'usageQuota' => 'nullable|integer|min:1',
            'startsAt' => 'nullable|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
            'daysOfWeek' => 'array',
            'daysOfWeek.*' => 'integer|between:0,6',
            'timeStart' => 'nullable|date_format:H:i',
            'timeEnd' => 'nullable|date_format:H:i|after:timeStart',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);

        $data = $this->validate();

        $payload = [
            'name' => $data['name'],
            'type' => $data['type'],
            'discount_type' => $data['discountType'],
            'discount_value' => (int) $data['discountValue'],
            'active' => $this->active,
            'min_items' => $data['minItems'] !== '' ? (int) $data['minItems'] : null,
            'max_discounted_items' => $data['maxDiscountedItems'] !== '' ? (int) $data['maxDiscountedItems'] : null,
            'usage_quota' => $data['usageQuota'] !== '' ? (int) $data['usageQuota'] : null,
            'starts_at' => $data['startsAt'] ?: null,
            'ends_at' => $data['endsAt'] ?: null,
            'days_of_week' => $this->daysOfWeek ? array_values($this->daysOfWeek) : null,
            'time_start' => $data['timeStart'] ?: null,
            'time_end' => $data['timeEnd'] ?: null,
        ];

        if ($this->promotionId) {
            Promotion::findOrFail($this->promotionId)->update($payload);
            session()->flash('success', 'Promotion updated.');
        } else {
            $promotion = Promotion::create($payload);
            $this->promotionId = $promotion->id;
            session()->flash('success', 'Promotion created.');

            // Coupon-type promos stay on the form so codes can be added.
            if ($this->type !== PromotionType::Coupon->value) {
                $this->redirect(route('admin.shop.promotions.index'), navigate: true);

                return;
            }
        }
    }

    public function addCoupon(): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);
        $this->requireSavedCouponPromo();

        $data = $this->validate([
            'newCode' => 'required|string|max:64|regex:/^[A-Za-z0-9\-]+$/|unique:coupons,code',
            'newCodeMaxUses' => 'nullable|integer|min:1',
        ]);

        Coupon::create([
            'promotion_id' => $this->promotionId,
            'code' => strtoupper($data['newCode']),
            'max_uses' => $data['newCodeMaxUses'] !== '' ? (int) $data['newCodeMaxUses'] : null,
        ]);

        $this->reset('newCode', 'newCodeMaxUses');
        session()->flash('success', 'Coupon code added.');
    }

    public function generateCoupons(): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);
        $this->requireSavedCouponPromo();

        $data = $this->validate([
            'genCount' => 'required|integer|min:1|max:1000',
            'genPrefix' => 'nullable|string|max:20|regex:/^[A-Za-z0-9\-]*$/',
            'genMaxUses' => 'nullable|integer|min:1',
        ]);

        $prefix = strtoupper($data['genPrefix']);
        $maxUses = $data['genMaxUses'] !== '' ? (int) $data['genMaxUses'] : null;
        $created = 0;

        for ($i = 0; $i < $data['genCount']; $i++) {
            // Retry until a unique code lands (collisions are rare at 8 random chars).
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = $prefix.strtoupper(Str::random(8));
                if (! Coupon::where('code', $code)->exists()) {
                    Coupon::create(['promotion_id' => $this->promotionId, 'code' => $code, 'max_uses' => $maxUses]);
                    $created++;
                    break;
                }
            }
        }

        session()->flash('success', "Generated {$created} coupon codes.");
    }

    public function deleteCoupon(int $couponId): void
    {
        abort_unless(auth()->user()->can(Permission::PromotionsManage->value), 403);

        Coupon::where('id', $couponId)->where('promotion_id', $this->promotionId)->delete();
        session()->flash('success', 'Coupon code removed.');
    }

    private function requireSavedCouponPromo(): void
    {
        abort_unless($this->promotionId && $this->type === PromotionType::Coupon->value, 400);
    }

    public function render()
    {
        $coupons = $this->promotionId
            ? Coupon::where('promotion_id', $this->promotionId)->latest()->get()
            : collect();

        return view('livewire.shop.promotion-form', [
            'types' => PromotionType::cases(),
            'discountTypes' => DiscountType::cases(),
            'coupons' => $coupons,
            'editing' => $this->promotionId !== null,
            'weekdays' => [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'],
        ]);
    }

    public function title(): string
    {
        return $this->promotionId ? 'Edit Promotion' : 'New Promotion';
    }
}
