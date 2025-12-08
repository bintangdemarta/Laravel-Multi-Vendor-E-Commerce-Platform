<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'total',
        'vat_amount',
        'marketplace_withholding',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'shipping_postal_code',
        'status',
        'notes',
        'payment_method',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'marketplace_withholding' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    // State Machine Methods
    public function markAsPaid(): void
    {
        DB::transaction(function () {
            $this->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);

            $this->recordStatusChange('paid', 'Payment confirmed');

            // Commit inventory: move reserved stock to actual sold
            foreach ($this->items as $item) {
                $item->sku->decrementStock($item->quantity);
            }
        });
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
        $this->recordStatusChange('processing', 'Order is being processed');
    }

    public function markAsShipped(): void
    {
        $this->update(['status' => 'shipped']);
        $this->recordStatusChange('shipped', 'Order has been shipped');
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
        $this->recordStatusChange('completed', 'Order completed successfully');
    }

    public function cancel(string $reason = null): void
    {
        DB::transaction(function () use ($reason) {
            // Release reserved stock
            foreach ($this->items as $item) {
                $item->sku->releaseStock($item->quantity);
                $item->update(['status' => 'cancelled']);
            }

            $this->update(['status' => 'cancelled']);
            $this->recordStatusChange('cancelled', $reason ?? 'Order cancelled');
        });
    }

    public function refund(string $reason = null): void
    {
        DB::transaction(function () use ($reason) {
            // Return stock
            foreach ($this->items as $item) {
                $item->sku->incrementStock($item->quantity);
                $item->update(['status' => 'refunded']);
            }

            $this->update(['status' => 'refunded']);
            $this->recordStatusChange('refunded', $reason ?? 'Order refunded');
        });
    }

    // Helper Methods
    public function recordStatusChange(string $status, string $notes = null): void
    {
        $this->statusHistories()->create([
            'status' => $status,
            'notes' => $notes,
            'user_id' => auth()->id(),
            'occurred_at' => now(),
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'paid']);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['completed', 'shipped']);
    }

    public function isPaid(): bool
    {
        return $this->status !== 'pending' && $this->paid_at !== null;
    }

    public function getVendors()
    {
        return $this->items()
            ->with('vendor')
            ->get()
            ->pluck('vendor')
            ->unique('id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Static Methods
    public static function generateOrderNumber(): string
    {
        $prefix = config('marketplace.order.number_prefix', 'MV');
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
