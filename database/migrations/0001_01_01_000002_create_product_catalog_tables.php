<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Brands
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });

        // Categories (Nested Set for hierarchy)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('lft')->default(0);
            $table->unsignedInteger('rgt')->default(0);
            $table->unsignedInteger('depth')->default(0);

            // Commission override per category
            $table->decimal('commission_rate', 5, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['lft', 'rgt', 'parent_id']);
            $table->index('slug');
            $table->index('is_active');
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Status
            $table->enum('status', ['draft', 'pending', 'published', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();

            // Features
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('wishlist_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('published_at');
            $table->index('is_featured');
            $table->fullText(['name', 'description']);
        });

        // SKUs (Stock Keeping Units)
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku_code', 50)->unique();
            $table->string('barcode', 50)->nullable();

            // Pricing
            $table->decimal('price', 15, 2);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('compare_at_price', 15, 2)->nullable();

            // Inventory
            $table->integer('stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('low_stock_threshold')->default(10);

            // Physical attributes
            $table->decimal('weight', 8, 2)->nullable(); // in grams
            $table->decimal('length', 8, 2)->nullable(); // in cm
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sold_count')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index('stock');
            $table->index('sku_code');
        });

        // Attributes (Size, Color, Material, etc.)
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Size, Color, Material
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Attribute Options (S, M, L / Red, Blue, etc.)
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->string('value'); // S, M, L / Red, Blue
            $table->string('color_code', 7)->nullable(); // #FF0000 for color swatches
            $table->timestamps();

            $table->index('attribute_id');
        });

        // Pivot: SKU <-> Attribute Options
        Schema::create('attribute_option_sku', function (Blueprint $table) {
            $table->foreignId('sku_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_option_id')->constrained()->onDelete('cascade');
            $table->primary(['sku_id', 'attribute_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_option_sku');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('skus');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
