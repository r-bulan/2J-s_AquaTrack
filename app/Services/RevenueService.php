<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function recordOrderDeliveredIncome(Order $order): ?Transaction
    {
        return DB::transaction(function () use ($order) {
            // Idempotency check: check if a Water Sales transaction already exists for this order
            $existing = Transaction::where('order_id', $order->id)
                ->where('category', 'Water Sales')
                ->first();

            if ($existing) {
                return $existing;
            }

            $transaction = Transaction::create([
                'type' => 'income',
                'category' => 'Water Sales',
                'amount' => $order->total_amount,
                'date' => now()->toDateString(),
                'description' => "Order #{$order->id} ({$order->gallon_type} Gallon x {$order->jug_count}) - {$order->customer_name}",
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
            ]);

            $this->activityLogService->log(
                action: 'Income Recorded',
                entityType: 'Transaction',
                entityId: $transaction->id,
                description: sprintf('Recorded Water Sales income of ₱%.2f for Order #%d', $transaction->amount, $order->id),
                newValues: $transaction->toArray()
            );

            return $transaction;
        });
    }
}
