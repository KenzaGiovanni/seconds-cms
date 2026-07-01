<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockPolicy;
use App\Models\Product;
use App\Models\ProductCategory;

it('creates a simple product with money cast and formatting', function () {
    $product = Product::create([
        'type' => ProductType::Simple,
        'name' => 'Kopi Susu',
        'slug' => 'kopi-susu',
        'status' => ProductStatus::Published,
        'price' => 25000,
        'stock' => 10,
        'stock_policy' => StockPolicy::Deny,
    ]);

    expect($product->price)->toBe(25000)
        ->and($product->currency)->toBe('IDR')
        ->and($product->formattedPrice())->toBe('Rp 25.000')
        ->and($product->isSimple())->toBeTrue();
});

it('scopes published products', function () {
    Product::create(['name' => 'A', 'slug' => 'a', 'status' => ProductStatus::Published, 'stock_policy' => StockPolicy::None]);
    Product::create(['name' => 'B', 'slug' => 'b', 'status' => ProductStatus::Draft, 'stock_policy' => StockPolicy::None]);

    expect(Product::published()->count())->toBe(1);
});

it('attaches categories', function () {
    $product = Product::create(['name' => 'Tote', 'slug' => 'tote', 'stock_policy' => StockPolicy::None]);
    $cat = ProductCategory::create(['name' => 'Bags', 'slug' => 'bags']);

    $product->categories()->attach($cat->id);

    expect($product->fresh()->categories)->toHaveCount(1)
        ->and($cat->fresh()->products)->toHaveCount(1);
});

it('creates a variable product with variants carrying their own price/stock/options', function () {
    $product = Product::create([
        'type' => ProductType::Variable,
        'name' => 'Tee',
        'slug' => 'tee',
        'status' => ProductStatus::Published,
        'stock_policy' => StockPolicy::Deny,
    ]);

    $variant = $product->variants()->create([
        'sku' => 'TEE-L-RED',
        'price' => 120000,
        'stock' => 3,
        'options' => ['size' => 'L', 'color' => 'Red'],
    ]);

    expect($product->isVariable())->toBeTrue()
        ->and($product->variants)->toHaveCount(1)
        ->and($variant->options)->toBe(['size' => 'L', 'color' => 'Red'])
        ->and($variant->label())->toBe('L / Red')
        ->and($variant->formattedPrice())->toBe('Rp 120.000');
});

it('decrements simple product stock and blocks when out under deny policy', function () {
    $product = Product::create([
        'name' => 'Mug', 'slug' => 'mug',
        'price' => 50000, 'stock' => 2, 'stock_policy' => StockPolicy::Deny,
    ]);

    expect($product->inStock(2))->toBeTrue();

    $product->decrementStock(2);

    expect($product->fresh()->stock)->toBe(0)
        ->and($product->fresh()->inStock(1))->toBeFalse();
});

it('never blocks or tracks under the none policy', function () {
    $product = Product::create([
        'name' => 'Ebook', 'slug' => 'ebook',
        'price' => 30000, 'stock' => 0, 'stock_policy' => StockPolicy::None,
    ]);

    expect($product->inStock(999))->toBeTrue();

    $product->decrementStock(5);

    expect($product->fresh()->stock)->toBe(0); // untouched
});

it('allows backorder to go negative', function () {
    $product = Product::create([
        'name' => 'Preorder', 'slug' => 'preorder',
        'price' => 90000, 'stock' => 1, 'stock_policy' => StockPolicy::Backorder,
    ]);

    expect($product->inStock(10))->toBeTrue();

    $product->decrementStock(3);

    expect($product->fresh()->stock)->toBe(-2);
});

it('decrements variant stock, not product stock', function () {
    $product = Product::create([
        'type' => ProductType::Variable, 'name' => 'Cap', 'slug' => 'cap',
        'stock_policy' => StockPolicy::Deny,
    ]);
    $variant = $product->variants()->create(['price' => 80000, 'stock' => 5]);

    $product->decrementStock(2, $variant);

    expect($variant->fresh()->stock)->toBe(3);
});
