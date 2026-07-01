<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Livewire\Shop\Checkout;
use App\Livewire\Shop\PaymentSettingsForm;
use App\Livewire\Shop\PaymentVerificationList;
use App\Livewire\Shop\ProofUpload;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Support\CartManager;
use App\Support\PaymentSettings;
use App\Support\ThemeManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    $manager = app(ThemeManager::class);
    if (! Theme::where('slug', 'default')->exists()) {
        $manager->install(base_path('themes/default'));
    }
    Theme::where('slug', 'default')->update(['status' => 'active']);

    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function manualFlowOrder(): Order
{
    $product = Product::create([
        'name' => 'Kettle', 'slug' => 'kettle-mf', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    app(CartManager::class)->addItem($product, 2);

    $component = Livewire::test(Checkout::class);
    foreach ([
        'name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '08123456789',
        'addressLine' => 'Jl. Sudirman No. 1', 'city' => 'Jakarta', 'postalCode' => '10220',
    ] as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    return Order::where('email', 'budi@example.com')->firstOrFail();
}

function paymentsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('checkout wiring: placing an order creates a pending manual payment with the window stamped', function () {
    $order = manualFlowOrder();

    expect($order->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($order->payment_due_at)->not->toBeNull()
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Pending)
        ->and($order->payments->first()->amount)->toBe($order->total);
});

it('order confirmation page shows the bank details and upload form while awaiting payment', function () {
    Setting::set('bank_name', 'Bank Central Asia');
    Setting::set('bank_account_number', '1234567890');
    Setting::set('bank_account_holder', 'Seconds Store');
    Setting::flushCache();

    $order = manualFlowOrder();

    $this->get('/order/'.$order->number)
        ->assertOk()
        ->assertSee('Bank Central Asia')
        ->assertSee('1234567890')
        ->assertSee('Submit proof');
});

it('the order owner can upload proof of payment, moving it to submitted', function () {
    Storage::fake('local');
    $order = manualFlowOrder();
    $payment = $order->payments->first();

    Livewire::test(ProofUpload::class, ['order' => $order])
        ->set('payerReference', 'BCA 08:00')
        ->set('proof', UploadedFile::fake()->image('proof.jpg'))
        ->call('upload');

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Submitted)
        ->and($payment->payer_reference)->toBe('BCA 08:00')
        ->and($payment->proof_path)->not->toBeNull();

    Storage::disk('local')->assertExists($payment->proof_path);
});

it('rejects an invalid file type for proof of payment', function () {
    Storage::fake('local');
    $order = manualFlowOrder();

    Livewire::test(ProofUpload::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->create('malware.exe', 10))
        ->call('upload')
        ->assertHasErrors(['proof']);

    expect($order->payments->first()->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('a non-owner cannot open the proof upload component for someone elses order', function () {
    $order = manualFlowOrder();

    // Fresh request context: no session flag, no matching auth user.
    session()->forget('last_order_number');

    Livewire::test(ProofUpload::class, ['order' => $order->fresh()])
        ->assertStatus(404);
});

it('admin confirms a submitted payment, marking the order paid', function () {
    Storage::fake('local');
    $order = manualFlowOrder();
    $payment = $order->payments->first();

    Livewire::test(ProofUpload::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->image('proof.jpg'))
        ->call('upload');

    Livewire::actingAs(paymentsAdmin())
        ->test(PaymentVerificationList::class)
        ->assertSee($order->number)
        ->call('confirm', $payment->id);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->verified_by)->not->toBeNull();
});

it('admin rejects a submitted payment, returning it to pending with a reason', function () {
    Storage::fake('local');
    $order = manualFlowOrder();
    $payment = $order->payments->first();

    Livewire::test(ProofUpload::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->image('proof.jpg'))
        ->call('upload');

    Livewire::actingAs(paymentsAdmin())
        ->test(PaymentVerificationList::class)
        ->call('startReject', $payment->id)
        ->set('rejectionReason', 'Amount does not match')
        ->call('reject');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($payment->fresh()->rejection_reason)->toBe('Amount does not match')
        ->and($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment);
});

it('a non-permitted user cannot access the payment verification queue', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/shop/payments')->assertForbidden();
});

it('saves bank details and the payment window from the admin settings screen', function () {
    Livewire::actingAs(paymentsAdmin())
        ->test(PaymentSettingsForm::class)
        ->set('bankName', 'Bank Mandiri')
        ->set('bankAccountNumber', '9988776655')
        ->set('bankAccountHolder', 'Seconds Store')
        ->set('bankInstructions', 'Transfer the exact amount.')
        ->set('windowMinutes', 60)
        ->call('save');

    Setting::flushCache();

    expect(PaymentSettings::bankDetails())->toBe([
        'bank_name' => 'Bank Mandiri',
        'account_number' => '9988776655',
        'account_holder' => 'Seconds Store',
        'instructions' => 'Transfer the exact amount.',
    ])->and(PaymentSettings::windowMinutes())->toBe(60);
});

it('proof of payment is not downloadable by an unpermitted user', function () {
    Storage::fake('local');
    $order = manualFlowOrder();
    $payment = $order->payments->first();

    Livewire::test(ProofUpload::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->image('proof.jpg'))
        ->call('upload');

    $this->get('/admin/shop/payments/'.$payment->id.'/proof')->assertRedirect('/admin/login');

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    actingAs($editor)->get('/admin/shop/payments/'.$payment->id.'/proof')->assertForbidden();
});
