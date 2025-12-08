<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->sku->price * $item->quantity;
        });
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getTotalWeightAttribute(): int
    {
        return $this->items->sum(function ($item) {
            return ($item->sku->weight ?? 0) * $item->quantity;
        });
    }

    // Methods
    public function getItemsByVendor()
    {
        return $this->items()
            ->with('sku.product.vendor')
            ->get()
            ->groupBy(function ($item) {
                return $item->sku->product->vendor_id;
            });
    }

    public function clearExpiredItems(): void
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->items()->delete();
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
