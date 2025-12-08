<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Searchable, InteractsWithMedia;

    protected $fillable = [
        'vendor_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'rejection_reason',
        'is_featured',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    // Scout Searchable Configuration
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'vendor' => $this->vendor->shop_name,
            'category' => $this->category->name,
            'brand' => $this->brand?->name,
            'min_price' => $this->skus->min('price'),
            'max_price' => $this->skus->max('price'),
        ];
    }

    public function searchableAs(): string
    {
        return 'products_index';
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper Methods
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getMinPrice(): float
    {
        return $this->skus()->min('price') ?? 0;
    }

    public function getMaxPrice(): float
    {
        return $this->skus()->max('price') ?? 0;
    }

    public function getAverageRating(): float
    {
        return $this->reviews()->approved()->avg('rating') ?? 0;
    }

    public function getTotalReviews(): int
    {
        return $this->reviews()->approved()->count();
    }

    public function hasStock(): bool
    {
        return $this->skus()->where('is_active', true)->where('stock', '>', 0)->exists();
    }
}
