<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address_line',
        'district_id',
        'postal_code',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line,
            $this->district->name,
            $this->district->city->full_name,
            $this->district->city->province->name,
            $this->postal_code,
        ];

        return implode(', ', array_filter($parts));
    }

    public function getCityAttribute()
    {
        return $this->district->city;
    }

    public function getProvinceAttribute()
    {
        return $this->district->city->province;
    }

    // Methods
    public function setAsDefault(): void
    {
        // Unset other default addresses for this user
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
