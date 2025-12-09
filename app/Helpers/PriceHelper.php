<?php

namespace App\Helpers;

class PriceHelper
{
    /**
     * Format price to Indonesian Rupiah
     */
    public static function format(float $price): string
    {
        return 'Rp ' . number_format($price, 0, ',', '.');
    }

    /**
     * Calculate discount percentage
     */
    public static function discountPercentage(float $originalPrice, float $discountedPrice): int
    {
        if ($originalPrice <= 0) {
            return 0;
        }

        return (int) round((($originalPrice - $discountedPrice) / $originalPrice) * 100);
    }

    /**
     * Calculate final price after discount
     */
    public static function applyDiscount(float $price, float $discountPercent): float
    {
        return $price * (1 - ($discountPercent / 100));
    }
}
