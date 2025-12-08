<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
    ];

    // Relationships
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function skus(): BelongsToMany
    {
        return $this->belongsToMany(Sku::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->attribute->name}: {$this->value}";
    }
}
