<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'shop_name',
        'slug',
        'description',
        'logo',
        'banner',
        'business_name',
        'npwp',
        'business_type',
        'business_documents',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'postal_code',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'commission_rate',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'business_documents' => 'array',
        'balance' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'rating' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function shippingSettings(): HasOne
    {
        return $this->hasOne(VendorShippingSetting::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class);
    }

    // Status Checks
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // Commission Methods
    public function getCommissionRate(): float
    {
        // Priority: Vendor override > Default from config
        return $this->commission_rate ?? config('marketplace.commission.default_rate');
    }

    // Balance Management
    public function addToBalance(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $this->increment('balance', $amount);
            $this->increment('total_earnings', $amount);
        });
    }

    public function deductFromBalance(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }

        $this->decrement('balance', $amount);
        return true;
    }

    public function canRequestPayout(): bool
    {
        $minBalance = config('marketplace.vendor.min_balance_for_payout');
        return $this->balance >= $minBalance;
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
            ->where('is_active', true);
    }
}
