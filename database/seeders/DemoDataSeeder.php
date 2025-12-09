<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Sku;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo data for testing
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding demo data...');

        // Create demo users
        $customer = User::create([
            'name' => 'Demo Customer',
            'email' => 'customer@demo.com',
            'password' => bcrypt('password'),
            'type' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Created demo customer');

        // Create demo vendors
        $vendors = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => "Vendor {$i}",
                'email' => "vendor{$i}@demo.com",
                'password' => bcrypt('password'),
                'type' => 'vendor',
                'email_verified_at' => now(),
            ]);

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'shop_name' => "Toko Demo {$i}",
                'slug' => "toko-demo-{$i}",
                'description' => "Toko demo untuk testing aplikasi",
                'npwp' => '123456789012345',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => "Vendor {$i}",
                'phone' => '081234567890',
                'address' => "Jl. Demo No. {$i}",
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12345',
                'status' => 'active',
            ]);

            $vendors[] = $vendor;
        }

        $this->command->info('✅ Created 3 demo vendors');

        // Create categories
        $categories = [];
        $categoryNames = ['Electronics', 'Fashion', 'Home & Living', 'Beauty', 'Sports'];
        foreach ($categoryNames as $name) {
            $categories[] = Category::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'description' => "Category for {$name}",
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Created ' . count($categories) . ' categories');

        // Create brands
        $brands = [];
        $brandNames = ['Samsung', 'Nike', 'Uniqlo', 'IKEA', 'Loreal'];
        foreach ($brandNames as $name) {
            $brands[] = Brand::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Created ' . count($brands) . ' brands');

        // Create products
        $productCount = 0;
        foreach ($vendors as $vendor) {
            for ($i = 1; $i <= 5; $i++) {
                $category = $categories[array_rand($categories)];
                $brand = $brands[array_rand($brands)];

                $productName = "{$brand->name} Product {$i}";

                $product = Product::create([
                    'vendor_id' => $vendor->id,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'name' => $productName,
                    'slug' => \Illuminate\Support\Str::slug($productName) . '-' . uniqid(),
                    'description' => "Demo product description for {$productName}. Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
                    'short_description' => "High quality {$productName}",
                    'status' => 'published',
                    'featured' => rand(0, 1) === 1,
                ]);

                // Create SKUs for each product
                for ($j = 1; $j <= 2; $j++) {
                    Sku::create([
                        'product_id' => $product->id,
                        'sku_code' => 'SKU-' . strtoupper(uniqid()),
                        'price' => rand(50000, 500000),
                        'compare_at_price' => rand(0, 1) ? rand(60000, 600000) : null,
                        'cost_price' => rand(30000, 300000),
                        'stock' => rand(5, 50),
                        'weight' => rand(100, 2000),
                        'is_active' => true,
                    ]);
                }

                $productCount++;
            }
        }

        $this->command->info("✅ Created {$productCount} products with SKUs");

        $this->command->info('🎉 Demo data seeding completed!');
        $this->command->info('');
        $this->command->info('Demo Accounts:');
        $this->command->info('Customer: customer@demo.com / password');
        $this->command->info('Vendor 1: vendor1@demo.com / password');
        $this->command->info('Vendor 2: vendor2@demo.com / password');
        $this->command->info('Vendor 3: vendor3@demo.com / password');
    }
}
