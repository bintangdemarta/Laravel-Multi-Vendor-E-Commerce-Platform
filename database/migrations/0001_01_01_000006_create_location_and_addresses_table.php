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
        // Indonesian Provinces
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('rajaongkir_id', 10)->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('rajaongkir_id');
        });

        // Indonesian Cities/Regencies
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['kota', 'kabupaten']); // City type
            $table->string('postal_code', 10)->nullable();
            $table->string('rajaongkir_id', 10)->nullable();
            $table->timestamps();

            $table->index('province_id');
            $table->index('rajaongkir_id');
            $table->index('type');
        });

        // Indonesian Districts (Kecamatan)
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('rajaongkir_id', 10)->nullable();
            $table->timestamps();

            $table->index('city_id');
            $table->index('rajaongkir_id');
        });

        // User Addresses
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label', 50)->default('Home'); // Home, Office, Other
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->text('address_line');
            $table->foreignId('district_id')->constrained()->onDelete('restrict');
            $table->string('postal_code', 10);
            $table->text('notes')->nullable(); // Delivery notes
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->index('district_id');
        });

        // Shipping Rates Cache (RajaOngkir responses)
        Schema::create('shipping_rate_caches', function (Blueprint $table) {
            $table->id();
            $table->string('origin_city_id', 10);
            $table->string('destination_city_id', 10);
            $table->string('courier', 20);
            $table->decimal('weight', 8, 2); // in grams
            $table->json('rates'); // Array of shipping options
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['origin_city_id', 'destination_city_id', 'courier']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rate_caches');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};
