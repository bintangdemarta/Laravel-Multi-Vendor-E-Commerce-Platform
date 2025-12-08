<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Create payout for vendor (weekly automated payout)
     */
    public function createPayout(Vendor $vendor): ?VendorPayout
    {
        $minimumPayout = config('marketplace.commission.minimum_payout');

        // Check if vendor has enough balance
        if ($vendor->balance < $minimumPayout) {
            return null;
        }

        // Get all completed order items not yet paid out
        $unpaidItems = OrderItem::byVendor($vendor->id)
            ->completed()
            ->unpaidOut()
            ->get();

        if ($unpaidItems->isEmpty()) {
            return null;
        }

        $amount = $unpaidItems->sum('vendor_earnings');

        // Double check amount meets minimum
        if ($amount < $minimumPayout) {
            return null;
        }

        return DB::transaction(function () use ($vendor, $amount, $unpaidItems) {
            // Create payout record
            $payout = VendorPayout::create([
                'vendor_id' => $vendor->id,
                'payout_number' => $this->generatePayoutNumber(),
                'amount' => $amount,
                'status' => 'pending',
                'method' => 'bank_transfer',
                'bank_details' => [
                    'bank_name' => $vendor->bank_name,
                    'account_number' => $vendor->bank_account_number,
                    'account_name' => $vendor->bank_account_name,
                ],
            ]);

            // Link order items to this payout
            foreach ($unpaidItems as $item) {
                $payout->items()->create([
                    'order_item_id' => $item->id,
                    'amount' => $item->vendor_earnings,
                ]);
            }

            return $payout;
        });
    }

    /**
     * Process payout (mark as completed and deduct from vendor balance)
     */
    public function processPayout(VendorPayout $payout, string $referenceNumber = null): void
    {
        DB::transaction(function () use ($payout, $referenceNumber) {
            // Deduct from vendor balance
            if (!$payout->vendor->deductFromBalance($payout->amount)) {
                throw new \Exception('Insufficient vendor balance for payout');
            }

            // Update payout status
            $payout->update([
                'status' => 'completed',
                'reference_number' => $referenceNumber,
                'processed_at' => now(),
            ]);
        });
    }

    /**
     * Process all pending payouts (for weekly cron job)
     */
    public function processWeeklyPayouts(): array
    {
        $vendors = Vendor::approved()
            ->where('balance', '>=', config('marketplace.commission.minimum_payout'))
            ->get();

        $results = [
            'processed' => 0,
            'failed' => 0,
            'total_amount' => 0,
            'payouts' => [],
        ];

        foreach ($vendors as $vendor) {
            try {
                $payout = $this->createPayout($vendor);

                if ($payout) {
                    $results['processed']++;
                    $results['total_amount'] += $payout->amount;
                    $results['payouts'][] = [
                        'vendor_id' => $vendor->id,
                        'payout_id' => $payout->id,
                        'amount' => $payout->amount,
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                \Log::error('Payout creation failed for vendor ' . $vendor->id, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Get vendor payout history
     */
    public function getVendorPayoutHistory(Vendor $vendor, int $limit = 10)
    {
        return VendorPayout::where('vendor_id', $vendor->id)
            ->with('items.orderItem')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending payout amount for vendor
     */
    public function getPendingPayoutAmount(Vendor $vendor): float
    {
        $unpaidItems = OrderItem::byVendor($vendor->id)
            ->completed()
            ->unpaidOut()
            ->get();

        return $unpaidItems->sum('vendor_earnings');
    }

    /**
     * Cancel payout (before processing)
     */
    public function cancelPayout(VendorPayout $payout, string $reason): void
    {
        if ($payout->status !== 'pending') {
            throw new \Exception('Can only cancel pending payouts');
        }

        DB::transaction(function () use ($payout, $reason) {
            // Delete payout items (releases them for future payout)
            $payout->items()->delete();

            // Update payout status
            $payout->update([
                'status' => 'failed',
                'notes' => $reason,
            ]);
        });
    }

    /**
     * Generate unique payout number
     */
    private function generatePayoutNumber(): string
    {
        $prefix = 'PO';
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Get payout statistics for reporting
     */
    public function getPayoutStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $payouts = VendorPayout::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_payouts' => $payouts->count(),
            'total_amount' => $payouts->sum('amount'),
            'completed' => $payouts->where('status', 'completed')->count(),
            'pending' => $payouts->where('status', 'pending')->count(),
            'failed' => $payouts->where('status', 'failed')->count(),
            'average_payout' => $payouts->count() > 0 ? $payouts->avg('amount') : 0,
            'largest_payout' => $payouts->max('amount'),
        ];
    }
}
