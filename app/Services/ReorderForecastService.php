<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;

class ReorderForecastService
{
    /**
     * Get customers due for refill for the Admin dashboard (up to $limit, default 6)
     */
    public function getDashboardDueCustomers(int $limit = 6): Collection
    {
        return Customer::whereNotNull('last_order_date')
            ->whereNotNull('avg_reorder_days')
            ->where('avg_reorder_days', '>', 0)
            ->get()
            ->filter(function (Customer $customer) {
                return $customer->isDueForRefill();
            })
            ->map(function (Customer $customer) {
                $daysSince = (int) now()->diffInDays($customer->last_order_date);
                $customer->days_since_last_order = $daysSince;
                $customer->cycle_status = ($daysSince >= $customer->avg_reorder_days) ? 'Overdue' : 'Due Now';
                return $customer;
            })
            ->sortByDesc('days_since_last_order')
            ->take($limit)
            ->values();
    }

    /**
     * Check if a specific customer is due within ~3 days for portal reminder
     */
    public function checkCustomerRefillDue(Customer $customer): array
    {
        if (!$customer->last_order_date || !$customer->avg_reorder_days) {
            return [
                'is_due' => false,
                'days_since' => 0,
                'days_until' => null,
                'message' => 'No refill prediction available yet.',
            ];
        }

        $daysSince = (int) now()->diffInDays($customer->last_order_date);
        $avg = $customer->avg_reorder_days;
        $daysUntil = $avg - $daysSince;

        $isDue = $daysSince >= ($avg - 1) || $daysUntil <= 3;

        $message = $isDue
            ? ($daysUntil <= 0
                ? "You are due for a refill! It's been {$daysSince} days since your last delivery."
                : "Your typical refill is coming up in approximately {$daysUntil} day(s). Would you like to order now?")
            : "Your water supply looks good! Average cycle is {$avg} days (last ordered {$daysSince} days ago).";

        return [
            'is_due' => $isDue,
            'days_since' => $daysSince,
            'days_until' => $daysUntil,
            'avg_cycle' => $avg,
            'message' => $message,
        ];
    }
}
