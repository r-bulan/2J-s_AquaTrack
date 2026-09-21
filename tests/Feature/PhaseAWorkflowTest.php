<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\RecurringOrder;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerSyncService;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\RecurringOrderService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class PhaseAWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure baseline prices
        Setting::set('round_gallon_price', '35.00');
        Setting::set('flat_gallon_price', '40.00');
    }

    /**
     * A1.1 & A1.2: Mixed Round + Flat Jug Orders calculation and validation.
     */
    public function test_mixed_round_and_flat_order_total_is_calculated_correctly_on_backend(): void
    {
        $customer = Customer::first();
        $orderService = app(OrderService::class);

        // 3 Round (@ ₱35) + 2 Flat (@ ₱40) = ₱105 + ₱80 = ₱185.00, 5 total jugs
        $order = $orderService->createOrder([
            'customer_id' => $customer->id,
            'round_count' => 3,
            'flat_count' => 2,
            'payment_method' => 'Cash',
        ]);

        $this->assertEquals(5, $order->jug_count);
        $this->assertEquals(3, $order->round_count);
        $this->assertEquals(2, $order->flat_count);
        $this->assertEquals(35.00, (float) $order->round_unit_price);
        $this->assertEquals(40.00, (float) $order->flat_unit_price);
        $this->assertEquals(185.00, (float) $order->total_amount);
        $this->assertEquals('Mixed', $order->gallon_type);
        $this->assertStringContainsString('3 Round + 2 Flat (5 Jugs)', $order->breakdown);
    }

    public function test_order_creation_rejects_zero_round_and_zero_flat_quantities(): void
    {
        $customerUser = User::where('role', 'customer')->first();

        // Customer portal endpoint attempt with 0 round and 0 flat
        $response = $this->actingAs($customerUser)->post('/portal/orders', [
            'round_count' => 0,
            'flat_count' => 0,
            'payment_method' => 'Cash',
        ]);

        $response->assertSessionHasErrors('round_count');
    }

    public function test_order_creation_backend_ignores_tampered_frontend_price(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $customer = $customerUser->customer;

        // User attempts to inject total_amount = 1.00 and unit_price = 0.50
        $response = $this->actingAs($customerUser)->post('/portal/orders', [
            'round_count' => 2,
            'flat_count' => 1,
            'unit_price' => 0.50,
            'total_amount' => 1.00,
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect('/portal?tab=orders');

        $latestOrder = Order::where('customer_id', $customer->id)->latest('id')->first();
        // Backend must calculate 2 * 35 + 1 * 40 = ₱110.00, ignoring tampered inputs
        $this->assertEquals(110.00, (float) $latestOrder->total_amount);
        $this->assertEquals(3, $latestOrder->jug_count);
    }

    /**
     * Adjustment #5: Order -> Delivery Round/Flat quantity consistency.
     */
    public function test_delivery_quantities_originate_directly_from_order_round_and_flat(): void
    {
        $customer = Customer::first();
        $rider = Rider::first();
        $orderService = app(OrderService::class);
        $deliveryService = app(DeliveryService::class);

        $order = $orderService->createOrder([
            'customer_id' => $customer->id,
            'round_count' => 4,
            'flat_count' => 3,
            'payment_method' => 'Cash',
        ]);

        $delivery = $deliveryService->assignRiderToOrder($order, $rider, 1);

        $this->assertEquals(7, $delivery->jug_count);
        $this->assertEquals(4, $delivery->round_count);
        $this->assertEquals(3, $delivery->flat_count);
        $this->assertEquals('Mixed', $delivery->gallon_type);
        $this->assertStringContainsString('4 Round + 3 Flat (7 Jugs)', $delivery->breakdown);
    }

    /**
     * Adjustment #4: Mixed-order jug synchronization and loyalty via CustomerSyncService.
     */
    public function test_mixed_order_synchronizes_exact_round_plus_flat_jug_count_to_loyalty_and_jug_ledger(): void
    {
        $customer = Customer::with(['loyaltyRecord', 'jugLedger'])->first();
        $rider = Rider::first();
        $orderService = app(OrderService::class);
        $deliveryService = app(DeliveryService::class);

        $initialHeld = $customer->jugLedger?->jugs_held ?? 0;
        $initialRefills = $customer->loyaltyRecord?->refills_count ?? 0;

        // Place mixed order: 3 Round + 2 Flat = 5 total jugs
        $order = $orderService->createOrder([
            'customer_id' => $customer->id,
            'round_count' => 3,
            'flat_count' => 2,
            'payment_method' => 'Cash',
        ]);

        $delivery = $deliveryService->assignRiderToOrder($order, $rider, 1);
        $deliveryService->startDelivery($delivery);

        // Complete delivery returning 1 empty jug
        $deliveryService->completeDelivery($delivery, returnedJugs: 1);

        $customer->refresh();
        $customer->load(['loyaltyRecord', 'jugLedger']);

        // Net jug increase should be exactly delivered (5) - returned (1) = +4
        $this->assertEquals($initialHeld + 4, $customer->jugLedger->jugs_held);

        // Loyalty refills should increase by exactly 5
        $threshold = (int) Setting::get('loyalty_refills_needed', 10);
        $expectedRefills = ($initialRefills + 5) % $threshold;
        $this->assertEquals($expectedRefills, $customer->loyaltyRecord->refills_count);
    }

    /**
     * Idempotency protection in customer sync and revenue.
     */
    public function test_repeated_synchronization_does_not_duplicate_jug_or_income_balances(): void
    {
        $customer = Customer::first();
        $rider = Rider::first();
        $orderService = app(OrderService::class);
        $deliveryService = app(DeliveryService::class);
        $customerSyncService = app(CustomerSyncService::class);

        $order = $orderService->createOrder([
            'customer_id' => $customer->id,
            'round_count' => 2,
            'flat_count' => 2,
            'payment_method' => 'Cash',
        ]);

        $delivery = $deliveryService->assignRiderToOrder($order, $rider, 1);
        $deliveryService->startDelivery($delivery);
        $deliveryService->completeDelivery($delivery, returnedJugs: 0);

        $customer->refresh();
        $heldAfterFirstSync = $customer->jugLedger->jugs_held;
        $waterSalesCount = Transaction::where('order_id', $order->id)->where('category', 'Water Sales')->count();
        $this->assertEquals(1, $waterSalesCount);

        // Attempt manual repeat sync
        $customerSyncService->syncDeliveredOrder($order);

        $customer->refresh();
        $this->assertEquals($heldAfterFirstSync, $customer->jugLedger->jugs_held);
        $this->assertEquals(1, Transaction::where('order_id', $order->id)->where('category', 'Water Sales')->count());
    }

    /**
     * A2.1 & A2.2 & Adjustment #6: Recurring order creation without redundant customer_name.
     */
    public function test_customer_can_create_recurring_order_with_mixed_quantities(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $customer = $customerUser->customer;

        $response = $this->actingAs($customerUser)->post('/portal/recurring', [
            'round_count' => 3,
            'flat_count' => 1,
            'frequency' => 'Weekly',
            'next_order_date' => now()->addDays(2)->toDateString(),
            'payment_method' => 'Cash',
            'preferred_time' => '8:00 AM - 10:00 AM',
            'notes' => 'Please ring the doorbell',
        ]);

        $response->assertRedirect('/portal?tab=recurring');
        $response->assertSessionHas('success');

        $schedule = RecurringOrder::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('Active', $schedule->status);
        $this->assertEquals(3, $schedule->round_count);
        $this->assertEquals(1, $schedule->flat_count);
        $this->assertEquals(4, $schedule->total_jugs);
        $this->assertEquals('Weekly', $schedule->frequency);
        $this->assertEquals($customer->id, $schedule->customer->id);
    }

    /**
     * A2.5 & A2.6: Pause, Resume, and Cancel recurring order with ownership authorization.
     */
    public function test_recurring_order_pause_resume_and_cancel_lifecycle(): void
    {
        $customerUser = User::where('role', 'customer')->first();
        $customer = $customerUser->customer;
        $recurringService = app(RecurringOrderService::class);

        $schedule = $recurringService->createRecurringOrder([
            'round_count' => 2,
            'flat_count' => 1,
            'frequency' => 'Biweekly',
            'next_order_date' => now()->addDays(3)->toDateString(),
        ], $customer);

        // 1. Pause
        $this->actingAs($customerUser)->post("/portal/recurring/{$schedule->id}/pause")
            ->assertSessionHas('success');
        $this->assertEquals('Paused', $schedule->fresh()->status);

        // 2. Resume
        $this->actingAs($customerUser)->post("/portal/recurring/{$schedule->id}/resume")
            ->assertSessionHas('success');
        $this->assertEquals('Active', $schedule->fresh()->status);

        // 3. Cancel
        $this->actingAs($customerUser)->post("/portal/recurring/{$schedule->id}/cancel")
            ->assertSessionHas('success');
        $this->assertEquals('Cancelled', $schedule->fresh()->status);
    }

    public function test_customer_cannot_manage_another_customers_recurring_order(): void
    {
        $customers = Customer::whereHas('user')->take(2)->get();
        if ($customers->count() < 2) {
            $this->markTestSkipped('Need at least 2 customer users to test ownership protection.');
        }

        $customerA = $customers[0];
        $customerB = $customers[1];

        $recurringService = app(RecurringOrderService::class);
        $scheduleA = $recurringService->createRecurringOrder([
            'round_count' => 2,
            'flat_count' => 0,
            'frequency' => 'Weekly',
            'next_order_date' => now()->addDay()->toDateString(),
        ], $customerA);

        // Customer B tries to pause Customer A's recurring order -> 403 Forbidden
        $response = $this->actingAs($customerB->user)->post("/portal/recurring/{$scheduleA->id}/pause");
        $response->assertForbidden();

        $this->assertEquals('Active', $scheduleA->fresh()->status);
    }

    /**
     * Adjustment #1: Recurring orders use applicable current pricing on generation.
     */
    public function test_recurring_order_uses_current_pricing_when_order_is_generated(): void
    {
        $customer = Customer::first();
        $recurringService = app(RecurringOrderService::class);
        $pricingService = app(PricingService::class);

        // Initial setup: prices are 35 and 40
        $schedule = $recurringService->createRecurringOrder([
            'round_count' => 2,
            'flat_count' => 2,
            'frequency' => 'Weekly',
            'next_order_date' => now()->toDateString(), // Due today
        ], $customer);

        // Admin updates prices before generation: Round ₱45, Flat ₱50
        $pricingService->updatePrices(45.00, 50.00);

        // Generate the order
        $order = $recurringService->generateOrder($schedule, now());

        $this->assertNotNull($order);
        // Order should have used the updated prices: 2*45 + 2*50 = ₱190.00
        $this->assertEquals(45.00, (float) $order->round_unit_price);
        $this->assertEquals(50.00, (float) $order->flat_unit_price);
        $this->assertEquals(190.00, (float) $order->total_amount);
    }

    /**
     * Adjustment #2: Recurring-order duplicate generation protection (database & application levels).
     */
    public function test_recurring_order_duplicate_generation_protection_application_and_database_levels(): void
    {
        $customer = Customer::first();
        $recurringService = app(RecurringOrderService::class);
        $today = now()->startOfDay();

        $schedule = $recurringService->createRecurringOrder([
            'round_count' => 3,
            'flat_count' => 0,
            'frequency' => 'Weekly',
            'next_order_date' => $today->toDateString(),
        ], $customer);

        // First generation run
        $order1 = $recurringService->generateOrder($schedule, $today);
        $this->assertNotNull($order1);
        $this->assertEquals($today->toDateString(), $order1->order_date->toDateString());

        // Second generation run for the same date must NOT create a duplicate order
        $schedule->refresh();
        $schedule->update(['next_order_date' => $today->toDateString()]); // Artificially rewind date to test duplicate guard
        $order2 = $recurringService->generateOrder($schedule, $today);

        // Should return the already existing order, NOT create a second order
        $ordersCount = Order::where('recurring_order_id', $schedule->id)
            ->whereDate('order_date', $today->toDateString())
            ->count();
        $this->assertEquals(1, $ordersCount);

        // Test database-level unique constraint directly
        $this->expectException(QueryException::class);
        Order::create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'order_date' => $today->toDateString(),
            'status' => 'Pending',
            'type' => 'Recurring',
            'jug_count' => 3,
            'round_count' => 3,
            'flat_count' => 0,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'round_unit_price' => 35.00,
            'flat_unit_price' => 40.00,
            'total_amount' => 105.00,
            'payment_status' => 'Unpaid',
            'payment_method' => 'Cash',
            'recurring' => true,
            'recurring_order_id' => $schedule->id, // Duplicate (recurring_order_id, order_date)!
        ]);
    }

    /**
     * Adjustment #3: Monthly recurring schedule safe month-end handling (e.g. Jan 31).
     */
    public function test_monthly_recurring_schedule_handles_january_31_and_month_ends_safely(): void
    {
        $recurringService = app(RecurringOrderService::class);

        // 1. From January 31, monthly should advance to February 28 (or 29 in leap year), NOT overflow to March
        $jan31 = Carbon::create(2026, 1, 31, 0, 0, 0);
        $nextFeb = $recurringService->calculateNextScheduledDate($jan31, 'Monthly', targetDay: 31);
        $this->assertEquals('2026-02-28', $nextFeb->toDateString());

        // 2. From February 28 (with targetDay=31), advancing to March should land on March 31!
        $nextMar = $recurringService->calculateNextScheduledDate($nextFeb, 'Monthly', targetDay: 31);
        $this->assertEquals('2026-03-31', $nextMar->toDateString());

        // 3. From March 31 (with targetDay=31), advancing to April (30 days) should land on April 30!
        $nextApr = $recurringService->calculateNextScheduledDate($nextMar, 'Monthly', targetDay: 31);
        $this->assertEquals('2026-04-30', $nextApr->toDateString());

        // 4. From April 30 (with targetDay=31), advancing to May should land on May 31!
        $nextMay = $recurringService->calculateNextScheduledDate($nextApr, 'Monthly', targetDay: 31);
        $this->assertEquals('2026-05-31', $nextMay->toDateString());
    }

    /**
     * Artisan command processing.
     */
    public function test_process_recurring_orders_artisan_command_generates_due_orders(): void
    {
        $customer = Customer::first();
        $recurringService = app(RecurringOrderService::class);
        $today = now()->startOfDay();

        $schedule = $recurringService->createRecurringOrder([
            'round_count' => 2,
            'flat_count' => 1,
            'frequency' => 'Weekly',
            'next_order_date' => $today->toDateString(),
        ], $customer);

        $this->artisan('orders:process-recurring', ['--date' => $today->toDateString()])
            ->expectsOutputToContain('Successfully generated')
            ->assertSuccessful();

        $this->assertTrue(Order::where('recurring_order_id', $schedule->id)->exists());
    }

    /**
     * A3: Deposit workflow removal on customer side.
     */
    public function test_customer_portal_does_not_display_deposit_status_or_deposit_paid(): void
    {
        $customerUser = User::where('role', 'customer')->first();

        $response = $this->actingAs($customerUser)->get('/portal?tab=home');
        $response->assertOk();

        // Must NOT display "Deposit Status:" or "Deposit Paid:"
        $response->assertDontSee('Deposit Status:');
        $response->assertDontSee('Deposit Paid:');
    }
}
