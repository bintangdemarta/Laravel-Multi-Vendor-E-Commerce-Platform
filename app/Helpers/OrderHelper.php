<?php

namespace App\Helpers;

class OrderHelper
{
    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'MV';
        $date = date('ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Check if order can be cancelled
     */
    public static function canBeCancelled(string $status): bool
    {
        return in_array($status, ['pending', 'paid']);
    }

    /**
     * Check if order can be refunded
     */
    public static function canBeRefunded(string $status): bool
    {
        return in_array($status, ['paid', 'processing', 'shipped']);
    }

    /**
     * Get next status in order workflow
     */
    public static function getNextStatus(string $currentStatus): ?string
    {
        $workflow = [
            'pending' => 'paid',
            'paid' => 'processing',
            'processing' => 'shipped',
            'shipped' => 'completed',
        ];

        return $workflow[$currentStatus] ?? null;
    }

    /**
     * Get available actions for order status
     */
    public static function getAvailableActions(string $status): array
    {
        $actions = [];

        if (self::canBeCancelled($status)) {
            $actions[] = 'cancel';
        }

        if (self::canBeRefunded($status)) {
            $actions[] = 'refund';
        }

        if ($status === 'completed') {
            $actions[] = 'review';
        }

        return $actions;
    }
}
