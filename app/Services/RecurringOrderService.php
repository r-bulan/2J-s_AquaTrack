<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RecurringOrder;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringOrderService
{
    public function __construct(
        protected OrderService $orderService,
        protected PricingService $pricingService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Create a new recurring order schedule for a customer.
     */
    public function createRecurringOrder(array $data, Customer $customer): RecurringOrder
    {
        return DB::transaction(function () use ($data, $customer) {
            $roundCount = max(0, (int) ($data['round_count'] ?? 0));
            $flatCount = max(0, (int) ($data['flat_count'] ?? 0));

            if ($roundCount === 0 && $flatCount === 0) {
                throw new \InvalidArgumentException("Recurring order must include at least one Round or Flat gallon.");
            }

            $frequency = $this->normalizeFrequency($data['frequency'] ?? 'Weekly');

            $nextOrderDate = !empty($data['next_order_date'])
                ? Carbon::parse($data['next_order_date'])->startOfDay()
                : now()->startOfDay()->addWeek();

            // Store target day of month (e.g., 31) for safe monthly date advancing without overflow
            $targetDay = $nextOrderDate->day;

            $deliveryAddress = !empty(trim($data['delivery_address'] ?? ''))
                ? trim($data['delivery_address'])
                : $customer->address;

            $recurringOrder = RecurringOrder::create([
                'customer_id' => $customer->id,
                'round_count' => $roundCount,
                'flat_count' => $flatCount,
                'frequency' => $frequency,
                'status' => 'Active',
                'next_order_date' => $nextOrderDate->toDateString(),
                'last_generated_date' => null,
                'target_day' => $targetDay,
                'payment_method' => $data['payment_method'] ?? 'Cash',
                'delivery_address' => $deliveryAddress,
                'preferred_time' => $data['preferred_time'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->activityLogService->log(
                action: 'Recurring Order Created',
                entityType: 'RecurringOrder',
                entityId: $recurringOrder->id,
                description: sprintf(
                    'Recurring order #%d created for %s (%s, Frequency: %s, Next Order: %s)',
                    $recurringOrder->id,
                    $customer->name,
                    $recurringOrder->breakdown,
                    $frequency,
                    $recurringOrder->next_order_date->format('M d, Y')
                ),
                newValues: $recurringOrder->toArray()
            );

            return $recurringOrder;
        });
    }

    /**
     * Pause an active recurring order.
     */
    public function pauseRecurringOrder(RecurringOrder $recurringOrder, Customer $customer): RecurringOrder
    {
        $this->authorizeCustomerOwnership($recurringOrder, $customer);

        if ($recurringOrder->status === 'Cancelled') {
            throw new \DomainException("Cancelled recurring orders cannot be paused.");
        }

        return DB::transaction(function () use ($recurringOrder, $customer) {
            $recurringOrder->update(['status' => 'Paused']);

            $this->activityLogService->log(
                action: 'Recurring Order Paused',
                entityType: 'RecurringOrder',
                entityId: $recurringOrder->id,
                description: sprintf('Recurring order #%d was paused by %s', $recurringOrder->id, $customer->name)
            );

            return $recurringOrder;
        });
    }

    /**
     * Resume a paused recurring order.
     */
    public function resumeRecurringOrder(RecurringOrder $recurringOrder, Customer $customer): RecurringOrder
    {
        $this->authorizeCustomerOwnership($recurringOrder, $customer);

        if ($recurringOrder->status === 'Cancelled') {
            throw new \DomainException("Cancelled recurring orders cannot be resumed.");
        }

        return DB::transaction(function () use ($recurringOrder, $customer) {
            // If the next order date is in the past, align it to next valid scheduled date
            $nextDate = Carbon::parse($recurringOrder->next_order_date)->startOfDay();
            if ($nextDate->isPast()) {
                $today = now()->startOfDay();
                $nextDate = $this->calculateNextScheduledDate($today, $recurringOrder->frequency, $recurringOrder->target_day);
            }

            $recurringOrder->update([
                'status' => 'Active',
                'next_order_date' => $nextDate->toDateString(),
            ]);

            $this->activityLogService->log(
                action: 'Recurring Order Resumed',
                entityType: 'RecurringOrder',
                entityId: $recurringOrder->id,
                description: sprintf(
                    'Recurring order #%d was resumed by %s. Next scheduled order: %s',
                    $recurringOrder->id,
                    $customer->name,
                    $nextDate->format('M d, Y')
                )
            );

            return $recurringOrder;
        });
    }

    /**
     * Cancel/stop a recurring order.
     */
    public function cancelRecurringOrder(RecurringOrder $recurringOrder, Customer $customer): RecurringOrder
    {
        $this->authorizeCustomerOwnership($recurringOrder, $customer);

        return DB::transaction(function () use ($recurringOrder, $customer) {
            $recurringOrder->update(['status' => 'Cancelled']);

            $this->activityLogService->log(
                action: 'Recurring Order Cancelled',
                entityType: 'RecurringOrder',
                entityId: $recurringOrder->id,
                description: sprintf('Recurring order #%d was cancelled by %s', $recurringOrder->id, $customer->name)
            );

            return $recurringOrder;
        });
    }

    /**
     * Find and generate all due active recurring orders.
     *
     * @param Carbon|null $asOfDate The reference date for generation (defaults to today)
     * @return Collection Collection of created Order instances
     */
    public function generateDueOrders(?Carbon $asOfDate = null): Collection
    {
        $date = ($asOfDate ?? now())->startOfDay();
        $dateString = $date->toDateString();

        $dueSchedules = RecurringOrder::where('status', 'Active')
            ->whereDate('next_order_date', '<=', $dateString)
            ->with('customer')
            ->orderBy('id')
            ->get();

        $createdOrders = collect();

        foreach ($dueSchedules as $schedule) {
            $order = $this->generateOrder($schedule, $date);
            if ($order) {
                $createdOrders->push($order);
            }
        }

        return $createdOrders;
    }

    /**
     * Generate an actual order from a recurring schedule for a given due date.
     * Guaranteed idempotent: protects against duplicate generation at both application and database levels.
     */
    public function generateOrder(RecurringOrder $recurringOrder, ?Carbon $asOfDate = null): ?Order
    {
        $date = ($asOfDate ?? now())->startOfDay();

        return DB::transaction(function () use ($recurringOrder, $date) {
            // Row-level lock to prevent concurrent workers from processing the same schedule
            $lockedSchedule = RecurringOrder::lockForUpdate()->find($recurringOrder->id);
            if (!$lockedSchedule || $lockedSchedule->status !== 'Active') {
                return null;
            }

            $orderDate = $lockedSchedule->next_order_date->toDateString();

            // Application-level idempotency check:
            // 1. Check if an order already exists for this recurring schedule and scheduled order date
            $existingOrder = Order::where('recurring_order_id', $lockedSchedule->id)
                ->whereDate('order_date', $orderDate)
                ->first();

            if ($existingOrder) {
                // Already generated for this date! Advance next_order_date to prevent future redundant attempts.
                $this->advanceScheduleToFuture($lockedSchedule, $date);
                return $existingOrder;
            }

            // 2. Also verify against last_generated_date
            if ($lockedSchedule->last_generated_date && $lockedSchedule->last_generated_date->toDateString() === $orderDate) {
                $this->advanceScheduleToFuture($lockedSchedule, $date);
                return null;
            }

            $customer = $lockedSchedule->customer;
            if (!$customer || $customer->status !== 'Active') {
                Log::warning("Recurring order #{$lockedSchedule->id} skipped: Customer is missing or inactive.");
                return null;
            }

            // Create normal order using current system pricing
            try {
                $order = $this->orderService->createOrder([
                    'customer_id' => $customer->id,
                    'order_date' => $orderDate,
                    'type' => 'Recurring',
                    'recurring' => true,
                    'recurring_order_id' => $lockedSchedule->id,
                    'round_count' => $lockedSchedule->round_count,
                    'flat_count' => $lockedSchedule->flat_count,
                    'payment_method' => $lockedSchedule->payment_method,
                    'delivery_address' => $lockedSchedule->delivery_address ?: $customer->address,
                    'preferred_time' => $lockedSchedule->preferred_time,
                    'notes' => $lockedSchedule->notes
                        ? $lockedSchedule->notes . " (Auto-generated from Recurring Schedule #{$lockedSchedule->id})"
                        : "Auto-generated from Recurring Schedule #{$lockedSchedule->id}",
                ]);
            } catch (QueryException $e) {
                // Database-level duplicate protection caught: duplicate unique key on (recurring_order_id, order_date)
                if (str_contains($e->getMessage(), 'orders_recurring_schedule_date_unique') || $e->getCode() == 23000) {
                    Log::info("Database-level duplicate prevented for Recurring Order #{$lockedSchedule->id} on date {$orderDate}");
                    $this->advanceScheduleToFuture($lockedSchedule, $date);
                    return Order::where('recurring_order_id', $lockedSchedule->id)->whereDate('order_date', $orderDate)->first();
                }
                throw $e;
            }

            // Advance schedule to next interval
            $this->advanceScheduleToFuture($lockedSchedule, $date, $orderDate);

            $this->activityLogService->log(
                action: 'Recurring Order Generated',
                entityType: 'Order',
                entityId: $order->id,
                description: sprintf(
                    'Order #%d was automatically generated from Recurring Schedule #%d for %s (%s, Total: ₱%.2f, Next schedule: %s)',
                    $order->id,
                    $lockedSchedule->id,
                    $customer->name,
                    $order->breakdown,
                    $order->total_amount,
                    $lockedSchedule->fresh()->next_order_date->format('M d, Y')
                )
            );

            return $order;
        });
    }

    /**
     * Advance recurring order next_order_date safely, taking into account target day of month (e.g. Jan 31).
     */
    protected function advanceScheduleToFuture(RecurringOrder $schedule, Carbon $asOfDate, ?string $justGeneratedDate = null): void
    {
        $currentNext = Carbon::parse($schedule->next_order_date)->startOfDay();
        $targetDay = $schedule->target_day ?? $currentNext->day;

        $newNext = $this->calculateNextScheduledDate($currentNext, $schedule->frequency, $targetDay);

        // If the schedule was behind by multiple cycles, advance until in the future
        while ($newNext->lte($asOfDate)) {
            $newNext = $this->calculateNextScheduledDate($newNext, $schedule->frequency, $targetDay);
        }

        $schedule->update([
            'last_generated_date' => $justGeneratedDate ?? $currentNext->toDateString(),
            'next_order_date' => $newNext->toDateString(),
        ]);
    }

    /**
     * Calculate the next scheduled date safely.
     * Safely handles month-end dates (such as Jan 31):
     * If the target day does not exist in the next month, it clamps to the last valid day of that month
     * instead of overflowing into the subsequent month.
     */
    public function calculateNextScheduledDate(Carbon $from, string $frequency, ?int $targetDay = null): Carbon
    {
        $from = $from->copy()->startOfDay();

        return match ($this->normalizeFrequency($frequency)) {
            'Biweekly' => $from->addWeeks(2),
            'Monthly' => $this->advanceMonthlySafe($from, $targetDay),
            default => $from->addWeek(),
        };
    }

    /**
     * Monthly date advancement with safe month-end handling (e.g. Jan 31 -> Feb 28 -> Mar 31).
     */
    public function advanceMonthlySafe(Carbon $from, ?int $targetDay = null): Carbon
    {
        $desiredDay = $targetDay ?? $from->day;

        // Move to first day of next month safely without overflow
        $nextMonth = $from->copy()->startOfMonth()->addMonthsNoOverflow(1);
        $daysInNextMonth = $nextMonth->daysInMonth;

        // Clamp to last valid day of next month if desired day exceeds available days
        $clampedDay = min($desiredDay, $daysInNextMonth);

        return $nextMonth->day($clampedDay);
    }

    /**
     * Verify customer ownership for recurring order mutations.
     */
    public function authorizeCustomerOwnership(RecurringOrder $recurringOrder, Customer $customer): void
    {
        if ((int) $recurringOrder->customer_id !== (int) $customer->id) {
            throw new AuthorizationException("You do not have permission to manage this recurring order schedule.");
        }
    }

    /**
     * Normalize frequency strings.
     */
    public function normalizeFrequency(string $frequency): string
    {
        $lower = strtolower(trim($frequency));
        if (str_contains($lower, 'bi') || str_contains($lower, '2')) {
            return 'Biweekly';
        }
        if (str_contains($lower, 'month')) {
            return 'Monthly';
        }
        return 'Weekly';
    }
}
