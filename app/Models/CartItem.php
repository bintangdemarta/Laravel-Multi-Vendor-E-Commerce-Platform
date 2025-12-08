<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'sku_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return $this->sku->price * $this->quantity;
    }

    public function getProductAttribute()
    {
        return $this->sku->product;
    }

    public function getVendorAttribute()
    {
        return $this->sku->product->vendor;
    }

    // Methods
    public function canFulfill(): bool
    {
        return $this->sku->hasStock($this->quantity);
    }

    public function updateQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            $this->delete();
            return true;
        }

        if ($this->sku->hasStock($quantity)) {
            $this->update(['quantity' => $quantity]);
            return true;
        }

        return false;
    }

    public function incrementQuantity(int $amount = 1): bool
    {
        return $this->updateQuantity($this->quantity + $amount);
    }

    public function decrementQuantity(int $amount = 1): bool
    {
        return $this->updateQuantity($this->quantity - $amount);
    }
}
