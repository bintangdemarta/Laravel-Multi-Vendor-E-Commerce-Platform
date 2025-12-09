<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorShippingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'mode',
        'origin_city_id',
        'origin_district_id',
        'enabled_couriers',
        'free_shipping_min_amount',
    ];

    protected $casts = [
        'enabled_couriers' => 'array',
        'free_shipping_min_amount' => 'decimal:2',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function originDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'origin_district_id');
    }

    // Methods
    public function isCourierEnabled(string $courierCode): bool
    {
        return in_array(strtolower($courierCode), $this->enabled_couriers ?? []);
    }

    public function hasFreeShipping(float $orderAmount): bool
    {
        return $this->free_shipping_min_amount && $orderAmount >= $this->free_shipping_min_amount;
    }
}
