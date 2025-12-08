<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name',
        'rajaongkir_id',
    ];

    // Relationships
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        return "{$this->name}, {$this->city->full_name}, {$this->city->province->name}";
    }
}
