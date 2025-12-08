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
        // Vendors table
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shop_name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();

            // Business Information
            $table->string('business_name');
            $table->string('npwp', 20)->unique(); // Tax ID (required for PMK 37/2025)
            $table->enum('business_type', ['individual', 'cv', 'pt', 'other'])->default('individual');
            $table->json('business_documents')->nullable(); // KTP, SIUP, TDP scans

            // Contact
            $table->string('phone', 20);
            $table->string('email');
            $table->text('address');
            $table->string('city');
            $table->string('province');
            $table->string('postal_code', 10);

            // Bank Information (for payouts)
            $table->string('bank_name');
            $table->string('bank_account_number', 50);
            $table->string('bank_account_name');

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Commission Override (nullable = use default)
            $table->decimal('commission_rate', 5, 2)->nullable();

            // Balance
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);

            // Metrics
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('status');
            $table->index(['status', 'approved_at']);
            $table->index('npwp');
        });

        // Vendor Shipping Settings
        Schema::create('vendor_shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->enum('mode', ['rajaongkir', 'custom'])->default('rajaongkir');
            $table->string('origin_city_id')->nullable(); // For RajaOngkir
            $table->json('enabled_couriers')->nullable(); // ['jne', 'tiki', 'pos']
            $table->json('custom_rates')->nullable(); // For custom shipping
            $table->timestamps();

            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_shipping_settings');
        Schema::dropIfExists('vendors');
    }
};
