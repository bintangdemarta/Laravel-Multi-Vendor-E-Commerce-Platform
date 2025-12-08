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
        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            $table->string('transaction_id')->unique(); // Midtrans transaction ID
            $table->string('payment_method', 50);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed', 'expired'])->default('pending');
            $table->json('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index(['order_id', 'status']);
            $table->index('status');
        });

        // Vendor Payouts
        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('restrict');
            $table->string('payout_number', 50)->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->enum('method', ['bank_transfer', 'manual'])->default('bank_transfer');
            $table->json('bank_details'); // Bank name, account number, account name
            $table->string('reference_number', 100)->nullable(); // Transfer reference
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('payout_number');
            $table->index('status');
            $table->index('created_at');
        });

        // Payout Items (track which orders are included)
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payout_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index('vendor_payout_id');
            $table->index('order_item_id');
        });

        // Tax Reports (PMK 37/2025 Compliance)
        Schema::create('tax_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('restrict');
            $table->string('report_number', 50)->unique();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');

            // Tax Summary
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_vat', 15, 2)->default(0);
            $table->decimal('total_withholding', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);

            $table->json('report_data')->nullable(); // Detailed breakdown
            $table->enum('status', ['draft', 'generated', 'submitted'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'period_type']);
            $table->index('report_number');
            $table->index(['period_start', 'period_end']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_reports');
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('payments');
    }
};
