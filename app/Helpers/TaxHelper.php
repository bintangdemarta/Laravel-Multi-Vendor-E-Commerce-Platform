<?php

namespace App\Helpers;

class TaxHelper
{
    /**
     * Validate Indonesian NPWP (Tax ID)
     * Format: XX.XXX.XXX.X-XXX.XXX
     */
    public static function validateNPWP(string $npwp): bool
    {
        // Remove dots and dashes
        $npwp = preg_replace('/[.-]/', '', $npwp);

        // Must be 15 digits
        if (strlen($npwp) !== 15) {
            return false;
        }

        // Must be numeric
        if (!is_numeric($npwp)) {
            return false;
        }

        return true;
    }

    /**
     * Format NPWP with proper separators
     */
    public static function formatNPWP(string $npwp): string
    {
        // Remove all non-numeric characters
        $npwp = preg_replace('/\D/', '', $npwp);

        // Format: XX.XXX.XXX.X-XXX.XXX
        if (strlen($npwp) === 15) {
            return substr($npwp, 0, 2) . '.' .
                substr($npwp, 2, 3) . '.' .
                substr($npwp, 5, 3) . '.' .
                substr($npwp, 8, 1) . '-' .
                substr($npwp, 9, 3) . '.' .
                substr($npwp, 12, 3);
        }

        return $npwp;
    }

    /**
     * Calculate VAT (11% for Indonesia)
     */
    public static function calculateVAT(float $amount, float $rate = 0.11): float
    {
        return $amount * $rate;
    }

    /**
     * Calculate marketplace withholding (PMK 37/2025: 2.5%)
     */
    public static function calculateMarketplaceWithholding(float $amount, float $rate = 0.025): float
    {
        return $amount * $rate;
    }

    /**
     * Calculate total tax burden
     */
    public static function calculateTotalTax(float $subtotal): array
    {
        $vat = self::calculateVAT($subtotal);
        $withholding = self::calculateMarketplaceWithholding($subtotal);

        return [
            'vat' => $vat,
            'marketplace_withholding' => $withholding,
            'total_tax' => $vat + $withholding,
        ];
    }
}
