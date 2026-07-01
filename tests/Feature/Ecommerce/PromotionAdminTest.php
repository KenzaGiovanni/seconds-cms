<?php

use App\Enums\Role;
use App\Livewire\Shop\PromotionForm;
use App\Livewire\Shop\PromotionList;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function promoAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('blocks an editor from promotions', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/shop/promotions')->assertForbidden();
});

it('lets an admin open promotions', function () {
    actingAs(promoAdmin())->get('/admin/shop/promotions')->assertOk();
});

it('404s promotions when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    actingAs(promoAdmin())->get('/admin/shop/promotions')->assertNotFound();
});

it('creates an automatic percentage promotion', function () {
    Livewire::actingAs(promoAdmin())
        ->test(PromotionForm::class)
        ->set('name', 'Weekend 15')
        ->set('type', 'automatic')
        ->set('discountType', 'percentage')
        ->set('discountValue', 15)
        ->set('daysOfWeek', [0, 6])
        ->call('save')
        ->assertHasNoErrors();

    $promo = Promotion::where('name', 'Weekend 15')->first();
    expect($promo)->not->toBeNull()
        ->and($promo->discount_value)->toBe(15)
        ->and($promo->days_of_week)->toBe([0, 6]);
});

it('validates percentage cannot exceed 100', function () {
    Livewire::actingAs(promoAdmin())
        ->test(PromotionForm::class)
        ->set('name', 'Too much')
        ->set('discountType', 'percentage')
        ->set('discountValue', 150)
        ->call('save')
        ->assertHasErrors(['discountValue']);
});

it('validates the end date is not before the start date', function () {
    Livewire::actingAs(promoAdmin())
        ->test(PromotionForm::class)
        ->set('name', 'Bad dates')
        ->set('discountValue', 10)
        ->set('startsAt', now()->addDays(5)->toDateString())
        ->set('endsAt', now()->addDays(1)->toDateString())
        ->call('save')
        ->assertHasErrors(['endsAt']);
});

it('adds a single coupon code to a coupon promotion', function () {
    $promo = Promotion::create([
        'name' => 'Coupon promo', 'type' => 'coupon',
        'discount_type' => 'percentage', 'discount_value' => 20,
    ]);

    Livewire::actingAs(promoAdmin())
        ->test(PromotionForm::class, ['id' => $promo->id])
        ->set('newCode', 'save20')
        ->set('newCodeMaxUses', 5)
        ->call('addCoupon')
        ->assertHasNoErrors();

    $coupon = Coupon::where('promotion_id', $promo->id)->first();
    expect($coupon->code)->toBe('SAVE20') // upper-cased
        ->and($coupon->max_uses)->toBe(5);
});

it('rejects a duplicate coupon code', function () {
    $promo = Promotion::create(['name' => 'P', 'type' => 'coupon', 'discount_type' => 'percentage', 'discount_value' => 10]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'DUPE']);

    Livewire::actingAs(promoAdmin())
        ->test(PromotionForm::class, ['id' => $promo->id])
        ->set('newCode', 'DUPE')
        ->call('addCoupon')
        ->assertHasErrors(['newCode']);
});

it('mass-generates unique coupon codes with a prefix', function () {
    $promo = Promotion::create(['name' => 'Batch', 'type' => 'coupon', 'discount_type' => 'fixed', 'discount_value' => 5000]);

    Livewire::actingAs(promoAdmin())
        ->test(PromotionForm::class, ['id' => $promo->id])
        ->set('genCount', 25)
        ->set('genPrefix', 'raya-')
        ->set('genMaxUses', 1)
        ->call('generateCoupons')
        ->assertHasNoErrors();

    $coupons = Coupon::where('promotion_id', $promo->id)->get();
    expect($coupons)->toHaveCount(25)
        ->and($coupons->pluck('code')->unique())->toHaveCount(25)
        ->and($coupons->every(fn ($c) => str_starts_with($c->code, 'RAYA-')))->toBeTrue()
        ->and($coupons->first()->max_uses)->toBe(1);
});

it('deletes a promotion and cascades its coupons', function () {
    $promo = Promotion::create(['name' => 'Doomed', 'type' => 'coupon', 'discount_type' => 'percentage', 'discount_value' => 10]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'BYE']);

    Livewire::actingAs(promoAdmin())
        ->test(PromotionList::class)
        ->call('delete', $promo->id);

    expect(Promotion::find($promo->id))->toBeNull()
        ->and(Coupon::where('code', 'BYE')->exists())->toBeFalse();
});
