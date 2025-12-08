<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;

class TaxService
{
    /**
     * Calculate tax for an order (PMK 37/2025 Compliance)
     */
    public function calculateOrderTax(Order $order): array
    {
        $vatRate = config('marketplace.tax.vat_rate'); // 11%
        $withholdingRate = config('marketplace.tax.marketplace_withholding_rate'); // 2.5%

        $vatAmount = round($order->subtotal * $vatRate, 2);
        $withholdingAmount = round($order->subtotal * $withholdingRate, 2);

        return [
            'vat_amount' => $vatAmount,
            'marketplace_withholding' => $withholdingAmount,
            'total_tax' => $vatAmount + $withholdingAmount,
        ];
    }

    /**
     * Calculate tax for individual order item
     */
    public function calculateItemTax(OrderItem $orderItem): float
    {
        $vatRate = config('marketplace.tax.vat_rate');
        return round($orderItem->subtotal * $vatRate, 2);
    }

    /**
     * Generate vendor tax report for PMK 37/2025 submission
     */
    public function generateVendorTaxReport(Vendor $vendor, \DateTime $startDate, \DateTime $endDate): array
    {
        $orderItems = OrderItem::byVendor($vendor->id)
            ->whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->with('order')
            ->get();

        $totalSales = $orderItems->sum('subtotal');
        $totalCommission = $orderItems->sum('commission_amount');
        $totalTax = $orderItems->sum('tax_amount');
        $netEarnings = $orderItems->sum('vendor_earnings');

        // Calculate marketplace withholding
        $withholdingRate = config('marketplace.tax.marketplace_withholding_rate');
        $totalWithholding = round($totalSales * $withholdingRate, 2);

        return [
            'vendor' => [
                'id' => $vendor->id,
                'shop_name' => $vendor->shop_name,
                'npwp' => $vendor->npwp,
                'business_name' => $vendor->business_name,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_transactions' => $orderItems->count(),
                'total_sales' => $totalSales,
                'total_commission' => $totalCommission,
                'total_vat' => $totalTax,
                'total_withholding' => $totalWithholding,
                'net_earnings' => $netEarnings,
            ],
            'transactions' => $orderItems->map(function ($item) {
                return [
                    'order_number' => $item->order->order_number,
                    'date' => $item->order->created_at->format('Y-m-d'),
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'commission' => $item->commission_amount,
                    'tax' => $item->tax_amount,
                    'vendor_earnings' => $item->vendor_earnings,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate platform-wide tax report
     */
    public function generatePlatformTaxReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $totalSales = $orders->sum('subtotal');
        $totalVAT = $orders->sum('vat_amount');
        $totalWithholding = $orders->sum('marketplace_withholding');

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_orders' => $orders->count(),
                'total_sales' => $totalSales,
                'total_vat_collected' => $totalVAT,
                'total_withholding' => $totalWithholding,
                'total_tax_liability' => $totalVAT + $totalWithholding,
            ],
            'vat_rate' => config('marketplace.tax.vat_rate') * 100 . '%',
            'withholding_rate' => config('marketplace.tax.marketplace_withholding_rate') * 100 . '%',
        ];
    }

    /**
     * Validate vendor NPWP format (Indonesian Tax ID)
     */
    public function validateNPWP(string $npwp): bool
    {
        // NPWP format: XX.XXX.XXX.X-XXX.XXX (15 digits)
        $npwpClean = preg_replace('/[^0-9]/', '', $npwp);

        return strlen($npwpClean) === 15;
    }

    /**
     * Format NPWP for display
     */
    public function formatNPWP(string $npwp): string
    {
        $npwpClean = preg_replace('/[^0-9]/', '', $npwp);

        if (strlen($npwpClean) !== 15) {
            return $npwp;
        }

        return sprintf(
            '%s.%s.%s.%s-%s.%s',
            substr($npwpClean, 0, 2),
            substr($npwpClean, 2, 3),
            substr($npwpClean, 5, 3),
            substr($npwpClean, 8, 1),
            substr($npwpClean, 9, 3),
            substr($npwpClean, 12, 3)
        );
    }
}
