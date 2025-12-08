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
        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');

            // Pricing
            $table->decimal('subtotal', 15, 2);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);

            // Tax Breakdown (PMK 37/2025)
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('marketplace_withholding', 15, 2)->default(0);

            // Shipping Address
            $table->string('shipping_name');
            $table->string('shipping_phone', 20);
            $table->text('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_province');
            $table->string('shipping_postal_code', 10);

            // Status
            $table->enum('status', [
                'pending',
                'paid',
                'processing',
                'shipped',
                'completed',
                'cancelled',
                'refunded'
            ])->default('pending');
            $table->text('notes')->nullable();

            // Payment
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('order_number');
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
            $table->index('paid_at');
        });

        // Order Items (multi-vendor split)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('sku_id')->constrained()->onDelete('restrict');
            $table->foreignId('vendor_id')->constrained()->onDelete('restrict');

            $table->string('product_name');
            $table->string('sku_code', 50);
            $table->json('variant_details')->nullable(); // {size: "M", color: "Blue"}

            // Pricing (snapshot at order time)
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 15, 2); // price * quantity

            // Commission (calculated at order time)
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('vendor_earnings', 15, 2); // subtotal - commission - tax

            // Tax
            $table->decimal('tax_amount', 15, 2)->default(0);

            // Shipping
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->string('courier_name')->nullable();
            $table->string('courier_service')->nullable();

            // Status (each vendor item can have different status)
            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'completed',
                'cancelled',
                'refunded'
            ])->default('pending');
            $table->string('tracking_number', 100)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'vendor_id']);
            $table->index('vendor_id');
            $table->index('status');
            $table->index('tracking_number');
        });

        // Order Status Histories (audit trail)
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('status', 50);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained(); // Who made the change
            $table->timestamp('occurred_at');

            $table->index('order_id');
            $table->index('order_item_id');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
