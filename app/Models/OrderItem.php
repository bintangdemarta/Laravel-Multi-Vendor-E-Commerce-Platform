<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sku_id',
        'vendor_id',
        'product_name',
        'sku_code',
        'variant_details',
        'price',
        'quantity',
        'subtotal',
        'commission_rate',
        'commission_amount',
        'vendor_earnings',
        'tax_amount',
        'shipping_cost',
        'courier_name',
        'courier_service',
        'status',
        'tracking_number',
    ];

    protected $casts = [
        'variant_details' => 'array',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_earnings' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payoutItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PayoutItem::class);
    }

    // Status Methods
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsShipped(string $trackingNumber, string $courierName = null): void
    {
        $this->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'courier_name' => $courierName ?? $this->courier_name,
            'shipped_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function canBeShipped(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPaidOut(): bool
    {
        return $this->payoutItem()->exists();
    }

    // Scopes
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUnpaidOut($query)
    {
        return $query->whereDoesntHave('payoutItem');
    }
}
