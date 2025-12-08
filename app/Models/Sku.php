<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Sku extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku_code',
        'barcode',
        'price',
        'cost_price',
        'compare_at_price',
        'stock',
        'reserved_stock',
        'low_stock_threshold',
        'weight',
        'length',
        'width',
        'height',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeOptions(): BelongsToMany
    {
        return $this->belongsToMany(AttributeOption::class);
    }

    public function cartItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Stock Management
    public function hasStock(int $quantity = 1): bool
    {
        return ($this->stock - $this->reserved_stock) >= $quantity;
    }

    public function getAvailableStock(): int
    {
        return max(0, $this->stock - $this->reserved_stock);
    }

    public function reserveStock(int $quantity): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        return DB::transaction(function () use ($quantity) {
            // Lock the row for update
            $sku = self::where('id', $this->id)->lockForUpdate()->first();

            if (!$sku->hasStock($quantity)) {
                return false;
            }

            $sku->increment('reserved_stock', $quantity);
            $this->reserved_stock = $sku->reserved_stock;

            return true;
        });
    }

    public function releaseStock(int $quantity): void
    {
        DB::transaction(function () use ($quantity) {
            $this->lockForUpdate();
            $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
        });
    }

    public function decrementStock(int $quantity): void
    {
        DB::transaction(function () use ($quantity) {
            $this->lockForUpdate();
            $this->decrement('stock', $quantity);
            $this->decrement('reserved_stock', $quantity);
            $this->increment('sold_count', $quantity);
        });
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    // Pricing
    public function getPriceWithTax(): float
    {
        $vatRate = config('marketplace.tax.vat_rate');
        return $this->price * (1 + $vatRate);
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentage(): float
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
    }

    // Status Checks
    public function isLowStock(): bool
    {
        return $this->getAvailableStock() <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->getAvailableStock() <= 0;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereRaw('(stock - reserved_stock) > 0');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(stock - reserved_stock) <= low_stock_threshold');
    }
}
