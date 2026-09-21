<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\JugLedger;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class CustomerSyncService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function syncDeliveredOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Row-level lock to prevent concurrent synchronization
            $lockedOrder = Order::lockForUpdate()->with('delivery')->find($order->id);
            if (!$lockedOrder) {
                return;
            }

            // Must only synchronize if the order is Delivered
            if ($lockedOrder->status !== 'Delivered') {
                return;
            }

            // Idempotency guard: If already synchronized, abort early without duplicating
            if ($lockedOrder->synced_at !== null) {
                return;
            }

            $customer = Customer::with(['loyaltyRecord', 'jugLedger', 'creditLedger'])->find($lockedOrder->customer_id);
            if (!$customer) {
                return;
            }

            // 1. Update Customer's last order date
            $customer->update([
                'last_order_date' => now()->toDateString(),
            ]);

            // 2. Synchronize Loyalty Record (Owner-configurable threshold, default 10 refills per 1 free refill)
            $loyalty = $customer->loyaltyRecord;
            $threshold = (int) Setting::get('loyalty_refills_needed', 10);
            if ($threshold < 1) {
                $threshold = 10;
            }

            if (!$loyalty) {
                $loyalty = $customer->loyaltyRecord()->create([
                    'customer_name' => $customer->name,
                    'refills_count' => 0,
                    'refills_needed' => $threshold,
                    'free_jugs_earned' => 0,
                ]);
            }

            $newRefills = $loyalty->refills_count + $lockedOrder->jug_count;
            $freeEarned = floor($newRefills / $threshold);
            $remaining = $newRefills % $threshold;

            $loyaltyUpdate = [
                'refills_count' => (int) $remaining,
                'refills_needed' => $threshold,
                'free_jugs_earned' => (int) ($loyalty->free_jugs_earned + $freeEarned),
            ];
            if ($freeEarned > 0) {
                $loyaltyUpdate['last_reward_date'] = now()->toDateString();
            }
            $loyalty->update($loyaltyUpdate);

            // 3. Synchronize Jug Ledger (Accounts for delivered gallons and customer-returned empty jugs)
            $delivery = $lockedOrder->delivery;
            $deliveredJugs = (int) $lockedOrder->jug_count;
            $returnedJugs = $delivery ? (int) ($delivery->returned_jugs ?? 0) : 0;
            $netJugChange = $deliveredJugs - $returnedJugs;

            $jugLedger = $customer->jugLedger;
            if (!$jugLedger) {
                $jugLedger = $customer->jugLedger()->create([
                    'customer_name' => $customer->name,
                    'jugs_held' => max(0, $netJugChange),
                    'jugs_borrowed_date' => now()->toDateString(),
                    'deposit_status' => 'Unpaid',
                    'deposit_amount' => 0,
                ]);
            } else {
                $jugLedger->update([
                    'jugs_held' => max(0, $jugLedger->jugs_held + $netJugChange),
                    'jugs_borrowed_date' => now()->toDateString(),
                ]);
            }

            // 4. Synchronize Credit Ledger if payment is Credit (Idempotent)
            if (strcasecmp($lockedOrder->payment_method, 'Credit') === 0) {
                $creditLedger = $customer->creditLedger;
                if (!$creditLedger) {
                    $creditLedger = $customer->creditLedger()->create([
                        'customer_name' => $customer->name,
                        'amount_owed' => $lockedOrder->total_amount,
                        'status' => 'Outstanding',
                    ]);
                } else {
                    $creditLedger->update([
                        'amount_owed' => $creditLedger->amount_owed + $lockedOrder->total_amount,
                        'status' => 'Outstanding',
                    ]);
                }
            }

            // 5. Mark order as synchronized to guarantee idempotency
            $lockedOrder->update([
                'synced_at' => now(),
            ]);

            $this->activityLogService->log(
                action: 'Customer Synced',
                entityType: 'Customer',
                entityId: $customer->id,
                description: sprintf(
                    'Synchronized Order #%d for %s (Loyalty: +%d refills, Jugs: +%d delivered, -%d returned, Credit: %s)',
                    $lockedOrder->id,
                    $customer->name,
                    $lockedOrder->jug_count,
                    $deliveredJugs,
                    $returnedJugs,
                    strcasecmp($lockedOrder->payment_method, 'Credit') === 0 ? "₱{$lockedOrder->total_amount}" : 'None'
                )
            );
        });
    }

    /**
     * Manually adjust a customer's jug balance by Owner/Admin.
     * Concurrency safe with lockForUpdate() on the jug ledger row inside a database transaction.
     * Preserves jugs_borrowed_date and logs adjustment delta and reason to activity_logs.
     */
    public function manuallyAdjustJugBalance(Customer $customer, int $newBalance, string $reason): JugLedger
    {
        return DB::transaction(function () use ($customer, $newBalance, $reason) {
            // Concurrency protection: read -> calculate -> update within the same transaction with lockForUpdate()
            $ledger = JugLedger::where('customer_id', $customer->id)->lockForUpdate()->first();

            if (!$ledger) {
                // If the ledger row does not exist yet, create it inside the transaction
                $ledger = JugLedger::create([
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'jugs_held' => 0,
                    'jugs_borrowed_date' => null, // Do not falsely set borrowed date during manual correction
                    'deposit_status' => 'Unpaid',
                    'deposit_amount' => 0,
                ]);

                // Lock the newly created row
                $ledger = JugLedger::where('id', $ledger->id)->lockForUpdate()->first();
            }

            $oldBalance = (int) $ledger->jugs_held;
            $delta = $newBalance - $oldBalance;

            // Update balance and customer_name if changed, but explicitly preserve existing jugs_borrowed_date
            $ledger->update([
                'jugs_held' => $newBalance,
                'customer_name' => $customer->name,
            ]);

            $sign = $delta > 0 ? "+{$delta}" : (string) $delta;

            // Audit the adjustment in activity_logs (no duplicate ledger table created)
            $this->activityLogService->log(
                action: 'Jug Balance Adjusted',
                entityType: 'Customer',
                entityId: $customer->id,
                description: sprintf(
                    'Admin adjusted jug balance for %s from %d to %d (%s jugs). Reason: %s',
                    $customer->name,
                    $oldBalance,
                    $newBalance,
                    $sign,
                    $reason
                ),
                oldValues: [
                    'jugs_held' => $oldBalance,
                ],
                newValues: [
                    'jugs_held' => $newBalance,
                    'adjustment' => $delta,
                    'reason' => $reason,
                ]
            );

            return $ledger;
        });
    }
}
