<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Turns on the ecommerce toggle and loads a few sample products (simple +
 * variable) with categories so the catalog admin and storefront have
 * something to look at. Run on demand (not part of DatabaseSeeder):
 *
 *   php artisan db:seed --class=DemoShopSeeder
 *
 * Idempotent - safe to re-run. Visit /shop, /admin/shop/products, /cart.
 */
class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('ecommerce', 'true');
        Setting::flushCache();

        $apparel = ProductCategory::firstOrCreate(
            ['slug' => 'apparel'],
            ['name' => 'Apparel'],
        );
        $accessories = ProductCategory::firstOrCreate(
            ['slug' => 'accessories'],
            ['name' => 'Accessories'],
        );

        $mug = Product::firstOrCreate(
            ['slug' => 'seconds-mug'],
            [
                'name' => 'Seconds Mug',
                'type' => 'simple',
                'status' => 'published',
                'description' => 'A sturdy ceramic mug for your morning coffee.',
                'price' => 85000,
                'sku' => 'MUG-001',
                'stock' => 25,
                'stock_policy' => 'deny',
            ],
        );
        $mug->categories()->syncWithoutDetaching([$accessories->id]);

        $tote = Product::firstOrCreate(
            ['slug' => 'canvas-tote-bag'],
            [
                'name' => 'Canvas Tote Bag',
                'type' => 'simple',
                'status' => 'published',
                'description' => 'Heavy-duty canvas tote, holds everything you need.',
                'price' => 120000,
                'sku' => 'TOTE-001',
                'stock' => 4,
                'stock_policy' => 'deny',
            ],
        );
        $tote->categories()->syncWithoutDetaching([$accessories->id]);

        $shirt = Product::firstOrCreate(
            ['slug' => 'classic-tee'],
            [
                'name' => 'Classic Tee',
                'type' => 'variable',
                'status' => 'published',
                'description' => 'Soft cotton tee, available in three sizes.',
                'stock_policy' => 'deny',
            ],
        );
        $shirt->categories()->syncWithoutDetaching([$apparel->id]);

        if ($shirt->variants()->count() === 0) {
            $shirt->variants()->createMany([
                ['sku' => 'TEE-S', 'price' => 150000, 'stock' => 10, 'options' => ['Size' => 'S']],
                ['sku' => 'TEE-M', 'price' => 150000, 'stock' => 8, 'options' => ['Size' => 'M']],
                ['sku' => 'TEE-L', 'price' => 150000, 'stock' => 0, 'options' => ['Size' => 'L']],
            ]);
        }

        Product::firstOrCreate(
            ['slug' => 'sticker-pack'],
            [
                'name' => 'Sticker Pack',
                'type' => 'simple',
                'status' => 'draft',
                'description' => 'A pack of five vinyl stickers. Coming soon.',
                'price' => 35000,
                'sku' => 'STK-001',
                'stock' => 0,
                'stock_policy' => 'none',
            ],
        );
    }
}
