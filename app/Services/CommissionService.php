<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Vendor;
use App\Models\Category;

class CommissionService
{
    /**
     * Calculate commission for an order item
     */
    public function calculateCommission(OrderItem $orderItem): array
    {
        $vendor = $orderItem->vendor;
        $category = $orderItem->sku->product->category;

        // Priority: Vendor override > Category override > Default
        $rate = $this->getApplicableRate($vendor, $category);

        $commissionAmount = round($orderItem->subtotal * $rate, 2);
        $vendorEarnings = round($orderItem->subtotal - $commissionAmount, 2);

        return [
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'vendor_earnings' => $vendorEarnings,

    /**
     * Process commissions for all items in an order
     */
    public function processOrderCommissions(\App\Models\Order $order): void
    {
        foreach ($order->items as $item) {
            $commission = $this->calculateCommission($item);

            // Update order item with commission data
            $item->update($commission);

            // Add earnings to vendor balance
            $item->vendor->addToBalance($commission['vendor_earnings']);
        }
    }

    /**
     * Calculate total platform commission for a period
     */
    public function calculatePlatformCommission(\DateTime $startDate, \DateTime $endDate): array
    {
        $orderItems = OrderItem::completed()
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();

        $totalCommission = $orderItems->sum('commission_amount');
        $totalVendorEarnings = $orderItems->sum('vendor_earnings');
        $totalSales = $orderItems->sum('subtotal');

        return [
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'total_vendor_earnings' => $totalVendorEarnings,
            'commission_percentage' => $totalSales > 0 ? ($totalCommission / $totalSales) * 100 : 0,
        ];
    }

    /**
     * Get commission breakdown by category
     */
    public function getCommissionBreakdownByCategory(\DateTime $startDate, \DateTime $endDate): array
    {
        $orderItems = OrderItem::with(['sku.product.category'])
            ->completed()
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();

        $breakdown = [];

        foreach ($orderItems as $item) {
            $categoryName = $item->sku->product->category->name;

            if (!isset($breakdown[$categoryName])) {
                $breakdown[$categoryName] = [
                    'total_sales' => 0,
                    'total_commission' => 0,
                    'average_rate' => 0,
                ];
            }

            $breakdown[$categoryName]['total_sales'] += $item->subtotal;
            $breakdown[$categoryName]['total_commission'] += $item->commission_amount;
        }

        // Calculate average rates
        foreach ($breakdown as $category => &$data) {
            $data['average_rate'] = $data['total_sales'] > 0
                ? ($data['total_commission'] / $data['total_sales']) * 100
                : 0;
        }

        return $breakdown;
    }
}
