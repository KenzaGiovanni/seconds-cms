<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Livewire\Shop\Checkout;
use App\Livewire\Shop\PaymentSettingsForm;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Payments\ManualGateway;
use App\Payments\PaymentService;
use App\Support\CartManager;
use App\Support\PaymentSettings;
use App\Support\ThemeManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

function xenditAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

function fakeXenditInvoice(int $status = 200, array $overrides = []): void
{
    Http::fake([
        '*/balance' => Http::response(['balance' => 1000000], 200),
        '*/v2/invoices' => Http::response(array_merge([
            'id' => 'inv_'.Str::random(10),
            'invoice_url' => 'https://checkout.xendit.co/web/inv_test123',
            'status' => 'PENDING',
        ], $overrides), (int) $status),
    ]);
}

it('activates xendit with valid keys, persists masked keys, and flips the provider', function () {
    fakeXenditInvoice();

    Livewire::actingAs(xenditAdmin())
        ->test(PaymentSettingsForm::class)
        ->set('xenditSecretKey', 'xnd_development_secret123')
        ->set('xenditPublicKey', 'xnd_public_abc')
        ->set('xenditWebhookToken', 'whtoken')
        ->set('xenditMethods', ['va', 'qris'])
        ->call('activateXendit');

    Setting::flushCache();

    expect(PaymentSettings::provider())->toBe(PaymentProvider::Xendit)
        ->and(PaymentSettings::maskedXenditSecretKey())->toEndWith('t123')
        ->and(PaymentSettings::xenditEnabledMethods())->toEqual([PaymentMethod::VirtualAccount, PaymentMethod::Qris]);
});

it('rejects xendit activation when the keys fail verification', function () {
    Http::fake(['*/balance' => Http::response(['message' => 'invalid api key'], 401)]);

    Livewire::actingAs(xenditAdmin())
        ->test(PaymentSettingsForm::class)
        ->set('xenditSecretKey', 'bad-key')
        ->set('xenditMethods', ['va'])
        ->call('activateXendit');

    Setting::flushCache();

    expect(PaymentSettings::provider())->toBe(PaymentProvider::Manual);
});

it('switches back to manual after xendit was active', function () {
    fakeXenditInvoice();
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_test');
    PaymentSettings::setXenditEnabledMethods([PaymentMethod::VirtualAccount]);
    PaymentSettings::setProvider(PaymentProvider::Xendit);

    Livewire::actingAs(xenditAdmin())
        ->test(PaymentSettingsForm::class)
        ->call('useManual');

    expect(PaymentSettings::provider())->toBe(PaymentProvider::Manual);
});

it('creates a pending xendit payment with the invoice external_id and snapshotted amount/currency', function () {
    fakeXenditInvoice(overrides: ['id' => 'inv_abc123']);
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_test');
    PaymentSettings::setProvider(PaymentProvider::Xendit);

    $product = Product::create([
        'name' => 'Kettle', 'slug' => 'kettle-xendit', 'type' => 'simple',
        'status' => 'published', 'price' => 75000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);
    app(CartManager::class)->addItem($product, 1);

    $component = Livewire::test(Checkout::class);
    foreach ([
        'name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '08123456789',
        'addressLine' => 'Jl. Sudirman No. 1', 'city' => 'Jakarta', 'postalCode' => '10220',
    ] as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    $order = Order::where('email', 'budi@example.com')->firstOrFail();
    $payment = $order->payments->first();

    expect($order->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($payment->gateway)->toBe(PaymentProvider::Xendit)
        ->and($payment->external_id)->toBe('inv_abc123')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toBe(75000)
        ->and($payment->currency)->toBe('IDR')
        ->and($payment->raw_payload['invoice_url'])->toBe('https://checkout.xendit.co/web/inv_test123');
});

it('redirects checkout to the xendit hosted invoice url', function () {
    fakeXenditInvoice(overrides: ['invoice_url' => 'https://checkout.xendit.co/web/inv_redirect_test']);
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_test');
    PaymentSettings::setProvider(PaymentProvider::Xendit);

    $product = Product::create([
        'name' => 'Mug', 'slug' => 'mug-xendit', 'type' => 'simple',
        'status' => 'published', 'price' => 30000, 'stock_policy' => 'none',
    ]);
    app(CartManager::class)->addItem($product, 1);

    $component = Livewire::test(Checkout::class);
    foreach ([
        'name' => 'Budi Santoso', 'email' => 'redirect@example.com', 'phone' => '08123456789',
        'addressLine' => 'Jl. Sudirman No. 1', 'city' => 'Jakarta', 'postalCode' => '10220',
    ] as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder')->assertRedirect('https://checkout.xendit.co/web/inv_redirect_test');
});

it('manual bank transfer stays selectable as a fallback once xendit is active', function () {
    fakeXenditInvoice();
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_test');
    PaymentSettings::setProvider(PaymentProvider::Xendit);

    expect(app(PaymentService::class)->gateway(PaymentProvider::Manual))
        ->toBeInstanceOf(ManualGateway::class);
});

it('surfaces a failed xendit invoice creation as a checkout error, not a crash', function () {
    Http::fake(['*/v2/invoices' => Http::response(['message' => 'bad request'], 400)]);
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_test');
    PaymentSettings::setProvider(PaymentProvider::Xendit);

    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget-xendit-fail', 'type' => 'simple',
        'status' => 'published', 'price' => 20000, 'stock_policy' => 'deny', 'stock' => 5,
    ]);
    app(CartManager::class)->addItem($product, 1);

    $component = Livewire::test(Checkout::class);
    foreach ([
        'name' => 'Budi Santoso', 'email' => 'fail@example.com', 'phone' => '08123456789',
        'addressLine' => 'Jl. Sudirman No. 1', 'city' => 'Jakarta', 'postalCode' => '10220',
    ] as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    expect(Order::where('email', 'fail@example.com')->first())->toBeNull() // rolled back
        ->and($product->fresh()->stock)->toBe(5); // never decremented
});

it('a non-permitted user cannot activate xendit', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/shop/payments/settings')->assertForbidden();
});
