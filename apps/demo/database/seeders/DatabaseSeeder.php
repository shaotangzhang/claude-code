<?php

declare(strict_types=1);

namespace Database\Seeders;

use Acme\Blog\Models\Article;
use Acme\Blog\Models\Category as BlogCategory;
use Acme\Catalog\Models\Brand;
use Acme\Catalog\Models\Category as CatalogCategory;
use Acme\Catalog\Models\Product;
use Acme\Catalog\Models\Sku;
use Acme\Commerce\Models\Campaign;
use Acme\Commerce\Models\Warehouse;
use Acme\Membership\Enums\BillingPeriod;
use Acme\Membership\Models\Plan;
use Acme\Membership\Models\Tier;
use Acme\Rbac\Models\Role;
use Acme\ShippingZones\Models\Zone;
use Acme\ShippingZones\Models\ZoneRate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * End-to-end seed: enough data so every package has something to show.
 * Idempotent — safe to re-run via php artisan db:seed.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super admin
        $super = User::firstOrCreate(
            ['email' => 'super@acme.test'],
            ['name' => 'Super Admin', 'password' => bcrypt('secret123')],
        );
        $superRole = Role::firstOrCreate(['key' => 'super-admin'], ['label' => 'Super admin']);
        DB::table('acme_rbac_role_user')->insertOrIgnore(['role_id' => $superRole->id, 'user_id' => $super->id]);

        // 2. Blog
        $blogCat = BlogCategory::firstOrCreate(['locale' => 'en', 'slug' => 'news'], ['name' => 'News']);
        Article::firstOrCreate(
            ['locale' => 'en', 'slug' => 'hello-world'],
            [
                'author_id'    => $super->id,
                'category_id'  => $blogCat->id,
                'title'        => 'Hello, world',
                'excerpt'      => 'First post on the demo site.',
                'body'         => "Welcome to the demo. This article is rendered through cms-core's layout pipeline, themed and indexed.\n\nMore posts coming soon.",
                'status'       => Article::STATUS_PUBLISHED,
                'published_at' => now(),
            ],
        );

        // 3. Catalog
        $brand     = Brand::firstOrCreate(['locale' => 'en', 'slug' => 'acme'], ['name' => 'Acme Co.']);
        $tShirtCat = CatalogCategory::firstOrCreate(['locale' => 'en', 'slug' => 'apparel'], ['name' => 'Apparel']);

        $tshirt = Product::firstOrCreate(
            ['locale' => 'en', 'slug' => 'acme-tee'],
            [
                'brand_id'    => $brand->id,
                'category_id' => $tShirtCat->id,
                'title'       => 'Acme T-Shirt',
                'summary'     => 'A wearable demo of the entire stack.',
                'description' => 'Polyester-free, ULID-stamped.',
                'status'      => Product::STATUS_PUBLISHED,
            ],
        );
        $sku = Sku::firstOrCreate(
            ['product_id' => $tshirt->id, 'code' => 'TEE-M'],
            ['price_cents' => 1999, 'currency' => 'USD', 'stock_label' => 'In stock', 'position' => 1],
        );

        // 4. Warehouse + initial stock
        $wh = Warehouse::firstOrCreate(['code' => 'WH-MAIN'], ['name' => 'Main warehouse']);
        DB::table('acme_commerce_stock_levels')->updateOrInsert(
            ['sku_id' => $sku->id, 'warehouse_id' => $wh->id],
            ['on_hand' => 100, 'reserved' => 0, 'updated_at' => now(), 'created_at' => now()],
        );

        // 5. Membership tier + plan
        $gold = Tier::firstOrCreate(['key' => 'gold'], ['name' => 'Gold', 'level' => 100,
            'perks_json' => [['label' => '10% off all orders'], ['label' => 'Priority shipping']]]);
        Plan::firstOrCreate(
            ['key' => 'gold-monthly'],
            ['tier_id' => $gold->id, 'name' => 'Gold · Monthly',
             'billing_period' => BillingPeriod::Monthly->value,
             'price_cents' => 999, 'currency' => 'USD', 'trial_days' => 7, 'active' => true],
        );

        // 6. Campaign — auto-applies a 10% timed discount on all carts
        Campaign::firstOrCreate(
            ['key' => 'launch-week'],
            ['name' => 'Launch week 10% off',
             'type' => Campaign::TYPE_TIMED_DISCOUNT,
             'rules_json' => ['scope' => 'cart', 'percent' => 10],
             'starts_at' => now()->subDay(),
             'ends_at'   => now()->addMonth(),
             'active'    => true],
        );

        // 7. Shipping zone: US standard
        $us = Zone::firstOrCreate(['key' => 'us'], ['name' => 'United States']);
        DB::table('acme_shipping_zone_countries')->insertOrIgnore(['zone_id' => $us->id, 'country_code' => 'US']);
        ZoneRate::firstOrCreate(
            ['zone_id' => $us->id, 'key' => 'standard', 'currency' => 'USD'],
            ['label' => 'USPS Ground', 'cost_cents' => 599, 'days_min' => 3, 'days_max' => 7],
        );

        $this->command?->info('Demo seed complete.');
        $this->command?->line('Login: super@acme.test / secret123');
    }
}
