<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CustomerSyncService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function syncDeliveredOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $customer = Customer::with(['loyaltyRecord', 'jugLedger', 'creditLedger'])->find($order->customer_id);
            if (!$customer) {
                return;
            }

            // 1. Update Customer's last order date
            $customer->update([
                'last_order_date' => now()->toDateString(),
            ]);

            // 2. Synchronize Loyalty Record (Reward 1 free jug per 10 refills)
            $loyalty = $customer->loyaltyRecord;
            if (!$loyalty) {
                $loyalty = $customer->loyaltyRecord()->create([
                    'customer_name' => $customer->name,
                    'refills_count' => 0,
                    'refills_needed' => 10,
                    'free_jugs_earned' => 0,
                ]);
            }

            $newRefills = $loyalty->refills_count + $order->jug_count;
            $needed = $loyalty->refills_needed ?: 10;
            $freeEarned = floor($newRefills / $needed);
            $remaining = $newRefills % $needed;

            $loyaltyUpdate = [
                'refills_count' => $remaining,
                'free_jugs_earned' => $loyalty->free_jugs_earned + $freeEarned,
            ];
            if ($freeEarned > 0) {
                $loyaltyUpdate['last_reward_date'] = now()->toDateString();
            }
            $loyalty->update($loyaltyUpdate);

            // 3. Synchronize Jug Ledger
            $jugLedger = $customer->jugLedger;
            if (!$jugLedger) {
                $jugLedger = $customer->jugLedger()->create([
                    'customer_name' => $customer->name,
                    'jugs_held' => $order->jug_count,
                    'jugs_borrowed_date' => now()->toDateString(),
                    'deposit_status' => 'Unpaid',
                    'deposit_amount' => 0,
                ]);
            } else {
                $jugLedger->update([
                    'jugs_held' => max(0, $jugLedger->jugs_held + $order->jug_count),
                    'jugs_borrowed_date' => now()->toDateString(),
                ]);
            }

            // 4. Synchronize Credit Ledger if payment is Credit
            if (strcasecmp($order->payment_method, 'Credit') === 0) {
                $creditLedger = $customer->creditLedger;
                if (!$creditLedger) {
                    $creditLedger = $customer->creditLedger()->create([
                        'customer_name' => $customer->name,
                        'amount_owed' => $order->total_amount,
                        'status' => 'Outstanding',
                    ]);
                } else {
                    $creditLedger->update([
                        'amount_owed' => $creditLedger->amount_owed + $order->total_amount,
                        'status' => 'Outstanding',
                    ]);
                }
            }

            $this->activityLogService->log(
                action: 'Customer Synced',
                entityType: 'Customer',
                entityId: $customer->id,
                description: sprintf('Synchronized loyalty, jug ledger, and last order date for %s following Order #%d delivery', $customer->name, $order->id)
            );
        });
    }
}
