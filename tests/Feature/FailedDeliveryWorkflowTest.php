<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\RecurringOrder;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\Services\RecurringOrderService;
use Carbon\Carbon;
use DomainException;
use Tests\TestCase;

class FailedDeliveryWorkflowTest extends TestCase
{
    protected User $admin;
    protected User $riderUserA;
    protected Rider $riderA;
    protected User $riderUserB;
    protected Rider $riderB;
    protected User $customerUser;
    protected Customer $customer;
    protected OrderService $orderService;
    protected DeliveryService $deliveryService;

    protected function setUp(): void
    {
        parent::setUp();

        // Baseline pricing
        Setting::set('round_gallon_price', '35.00');
        Setting::set('flat_gallon_price', '40.00');

        $this->orderService = app(OrderService::class);
        $this->deliveryService = app(DeliveryService::class);

        // Fetch or create Admin
        $this->admin = User::firstOrCreate(
            ['email' => 'admin@twojs.test'],
            ['name' => 'Admin Owner', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        // Rider A
        $this->riderUserA = User::firstOrCreate(
            ['email' => 'rider.test.a@twojs.test'],
            ['name' => 'Rider Alpha', 'password' => bcrypt('password'), 'role' => 'rider']
        );
        $this->riderA = Rider::firstOrCreate(
            ['user_id' => $this->riderUserA->id],
            ['name' => 'Rider Alpha', 'phone' => '09170000001', 'status' => 'Active', 'wage_rate' => 50]
        );

        // Rider B
        $this->riderUserB = User::firstOrCreate(
            ['email' => 'rider.test.b@twojs.test'],
            ['name' => 'Rider Bravo', 'password' => bcrypt('password'), 'role' => 'rider']
        );
        $this->riderB = Rider::firstOrCreate(
            ['user_id' => $this->riderUserB->id],
            ['name' => 'Rider Bravo', 'phone' => '09170000002', 'status' => 'Active', 'wage_rate' => 50]
        );

        // Customer
        $this->customerUser = User::firstOrCreate(
            ['email' => 'customer.test@twojs.test'],
            ['name' => 'Test Customer', 'password' => bcrypt('password'), 'role' => 'customer']
        );
        $this->customer = Customer::firstOrCreate(
            ['user_id' => $this->customerUser->id],
            ['name' => 'Test Customer', 'email' => 'customer.test@twojs.test', 'phone' => '09180000001', 'address' => '123 Test St, Barangay 1', 'status' => 'Active']
        );
    }

    protected function createAssignedDelivery(Rider $rider): Delivery
    {
        $order = $this->orderService->createOrder([
            'customer_id' => $this->customer->id,
            'round_count' => 2,
            'flat_count' => 1,
            'payment_method' => 'Cash',
        ]);

        return $this->deliveryService->assignRiderToOrder($order, $rider, 10);
    }

    /**
     * Test 1: Rider can report failure for their assigned delivery with predefined reason.
     */
    public function test_rider_can_report_failure_for_assigned_delivery_with_predefined_reason(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);

        $response = $this->actingAs($this->riderUserA)->post(route('deliveries.fail', $delivery), [
            'reason' => 'Customer unavailable',
            'notes' => 'Gate locked, no answer after 3 phone calls.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $delivery->refresh();
        $this->assertEquals('Failed', $delivery->status);
        $this->assertEquals('Customer unavailable', $delivery->failure_reason);
        $this->assertEquals('Gate locked, no answer after 3 phone calls.', $delivery->failure_notes);
        $this->assertEquals(Delivery::RESOLUTION_PENDING_REVIEW, $delivery->failure_resolution);
        $this->assertNotNull($delivery->failed_at);
        $this->assertEquals($this->riderUserA->id, $delivery->failed_by_user_id);

        // Order status synced to Delivery Failed
        $this->assertEquals('Delivery Failed', $delivery->order->status);

        // First attempt recorded in delivery_attempts
        $this->assertDatabaseHas('delivery_attempts', [
            'delivery_id' => $delivery->id,
            'rider_id' => $this->riderA->id,
            'attempt_number' => 1,
            'status' => 'Failed',
            'failure_reason' => 'Customer unavailable',
        ]);
    }

    /**
     * Test 2: Rider cannot report failure for another rider's delivery.
     */
    public function test_rider_cannot_report_failure_for_another_riders_delivery(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);

        // Rider B attempts to fail Rider A's delivery
        $response = $this->actingAs($this->riderUserB)->post(route('deliveries.fail', $delivery), [
            'reason' => 'Customer unavailable',
        ]);

        $response->assertForbidden();

        $delivery->refresh();
        $this->assertEquals('Assigned', $delivery->status);
    }

    /**
     * Test 3: Failure reason validation and "Other" requires notes.
     */
    public function test_failure_reason_validation_and_other_requires_notes(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);

        // Missing reason
        $response = $this->actingAs($this->riderUserA)->post(route('deliveries.fail', $delivery), [
            'reason' => '',
        ]);
        $response->assertSessionHasErrors(['reason']);

        // Invalid reason
        $response = $this->actingAs($this->riderUserA)->post(route('deliveries.fail', $delivery), [
            'reason' => 'Alien invasion',
        ]);
        $response->assertSessionHasErrors(['reason']);

        // 'Other' without notes fails validation
        $response = $this->actingAs($this->riderUserA)->post(route('deliveries.fail', $delivery), [
            'reason' => 'Other',
            'notes' => '',
        ]);
        $response->assertSessionHasErrors(['notes']);

        // 'Other' with notes succeeds
        $response = $this->actingAs($this->riderUserA)->post(route('deliveries.fail', $delivery), [
            'reason' => 'Other',
            'notes' => 'Road flooded, unable to access street.',
        ]);
        $response->assertSessionHasNoErrors();
        $delivery->refresh();
        $this->assertEquals('Failed', $delivery->status);
        $this->assertEquals('Road flooded, unable to access street.', $delivery->failure_notes);
    }

    /**
     * Test 4: Failed delivery does not create revenue or trigger customer synchronization.
     */
    public function test_failed_delivery_does_not_create_revenue_or_sync_customer_records(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $order = $delivery->order;

        $this->deliveryService->startDelivery($delivery);
        $this->deliveryService->failDelivery($delivery, 'Wrong/incomplete address', 'No house number given', $this->riderUserA);

        // 1. Zero income transactions
        $this->assertDatabaseMissing('transactions', [
            'order_id' => $order->id,
            'category' => 'Water Sales',
        ]);

        // 2. Order synced_at is null
        $order->refresh();
        $this->assertNull($order->synced_at);

        // 3. Loyalty and jug ledgers unchanged
        $customer = $this->customer->fresh(['loyaltyRecord', 'jugLedger']);
        $this->assertEquals(0, $customer->loyaltyRecord?->refills_count ?? 0);
    }

    /**
     * Test 5: Owner can view failed deliveries queue.
     */
    public function test_owner_can_view_failed_deliveries_queue(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $this->deliveryService->failDelivery($delivery, 'Customer refused delivery', 'Refused to pay COD', $this->riderUserA);

        $response = $this->actingAs($this->admin)->get(route('deliveries.index', ['tab' => 'failed']));

        $response->assertOk();
        $response->assertSee('Customer refused delivery');
        $response->assertSee('Refused to pay COD');
        $response->assertSee('Retry Delivery');
        $response->assertSee('Cancel Order');
    }

    /**
     * Test 6: Owner can retry failed delivery and assign a different rider.
     */
    public function test_owner_can_retry_and_reschedule_failed_delivery_with_different_rider(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $order = $delivery->order;

        $this->deliveryService->failDelivery($delivery, 'Customer unavailable', 'Not home', $this->riderUserA);
        $this->assertEquals(0, $delivery->retry_count);

        $newDate = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->admin)->post(route('deliveries.retry', $delivery), [
            'rider_id' => $this->riderB->id,
            'delivery_date' => $newDate,
            'preferred_time' => '2:00 PM - 4:00 PM',
            'route_order' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $delivery->refresh();
        $order->refresh();

        // Kept same IDs
        $this->assertEquals($delivery->id, $order->delivery->id);
        $this->assertEquals('Assigned', $delivery->status);
        $this->assertEquals('Confirmed', $order->status);
        $this->assertEquals($this->riderB->id, $delivery->rider_id);
        $this->assertEquals($this->riderB->name, $delivery->rider_name);
        $this->assertEquals($newDate, $delivery->delivery_date->toDateString());
        $this->assertEquals(1, $delivery->retry_count);
        $this->assertEquals(Delivery::RESOLUTION_RESCHEDULED, $delivery->failure_resolution);

        // Previous failure reason preserved on delivery for reference
        $this->assertEquals('Customer unavailable', $delivery->failure_reason);

        // Second attempt created in delivery_attempts with status Assigned
        $this->assertDatabaseHas('delivery_attempts', [
            'delivery_id' => $delivery->id,
            'rider_id' => $this->riderB->id,
            'attempt_number' => 2,
            'status' => 'Assigned',
        ]);

        // New rider sees the delivery in their queue
        $queueResponse = $this->actingAs($this->riderUserB)->get(route('deliveries.index', ['tab' => 'queue']));
        $queueResponse->assertOk();
        $queueResponse->assertSee($this->customer->name);
    }

    /**
     * Test 7: Multi-failure cycle: Failed -> Retry -> Failed -> Retry -> Delivered.
     */
    public function test_multi_failure_cycle_preserves_attempts_and_syncs_revenue_once(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $order = $delivery->order;

        // Attempt 1: Rider A starts and fails
        $this->deliveryService->startDelivery($delivery);
        $this->deliveryService->failDelivery($delivery, 'Customer unavailable', 'First try failed', $this->riderUserA);
        $delivery->refresh();
        $this->assertEquals(0, $delivery->retry_count);

        // Attempt 2: Owner retries with Rider B
        $this->deliveryService->retryDelivery($delivery, $this->riderB, now()->addDay()->toDateString(), null, 99, $this->admin);
        $delivery->refresh();
        $this->assertEquals(1, $delivery->retry_count);

        // Rider B starts and fails
        $this->deliveryService->startDelivery($delivery);
        $this->deliveryService->failDelivery($delivery, 'Wrong/incomplete address', 'House not found', $this->riderUserB);
        $delivery->refresh();
        $this->assertEquals('Failed', $delivery->status);
        $this->assertEquals(1, $delivery->retry_count);

        // Attempt 3: Owner retries with Rider A
        $this->deliveryService->retryDelivery($delivery, $this->riderA, now()->addDays(2)->toDateString(), 'Morning', 1, $this->admin);
        $delivery->refresh();
        $this->assertEquals(2, $delivery->retry_count);

        // Rider A starts and delivers
        $this->deliveryService->startDelivery($delivery);
        $this->deliveryService->completeDelivery($delivery, null, null, 'Delivered on 3rd attempt', 3);

        $delivery->refresh();
        $order->refresh();

        // Delivery & Order final state
        $this->assertEquals('Delivered', $delivery->status);
        $this->assertEquals('Delivered', $order->status);
        $this->assertEquals(Delivery::RESOLUTION_RESOLVED, $delivery->failure_resolution);
        $this->assertEquals(2, $delivery->retry_count);

        // Exactly 3 delivery attempts in history
        $this->assertEquals(3, DeliveryAttempt::where('delivery_id', $delivery->id)->count());

        $attempts = DeliveryAttempt::where('delivery_id', $delivery->id)->orderBy('attempt_number')->get();
        $this->assertEquals('Failed', $attempts[0]->status);
        $this->assertEquals('Customer unavailable', $attempts[0]->failure_reason);
        $this->assertEquals($this->riderA->id, $attempts[0]->rider_id);

        $this->assertEquals('Failed', $attempts[1]->status);
        $this->assertEquals('Wrong/incomplete address', $attempts[1]->failure_reason);
        $this->assertEquals($this->riderB->id, $attempts[1]->rider_id);

        $this->assertEquals('Delivered', $attempts[2]->status);
        $this->assertEquals($this->riderA->id, $attempts[2]->rider_id);

        // Financial income recorded EXACTLY ONCE
        $this->assertEquals(1, Transaction::where('order_id', $order->id)->where('category', 'Water Sales')->count());

        // Order has synced_at timestamp
        $this->assertNotNull($order->synced_at);

        // Customer jug ledger synchronized
        $this->assertNotNull($this->customer->fresh('jugLedger')->jugLedger);
    }

    /**
     * Test 8: Owner can cancel order after failed delivery.
     */
    public function test_owner_can_cancel_order_after_failed_delivery(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $order = $delivery->order;

        $this->deliveryService->failDelivery($delivery, 'Customer refused delivery', 'Customer moved to another town', $this->riderUserA);

        $response = $this->actingAs($this->admin)->post(route('deliveries.cancel', $delivery), [
            'reason' => 'Customer confirmed order cancellation via phone.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $delivery->refresh();
        $order->refresh();

        $this->assertEquals('Failed', $delivery->status);
        $this->assertEquals(Delivery::RESOLUTION_CANCELLED, $delivery->failure_resolution);
        $this->assertEquals('Cancelled', $order->status);
        $this->assertStringContainsString('Customer confirmed order cancellation via phone.', $order->notes);

        // Zero revenue recorded
        $this->assertDatabaseMissing('transactions', [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Test 9: Recurring-generated order can fail and retry without duplicating recurring schedule.
     */
    public function test_recurring_generated_order_can_fail_and_retry_without_affecting_schedule(): void
    {
        $recurringService = app(RecurringOrderService::class);

        $nextDate = Carbon::parse('2026-10-01');
        $recurring = RecurringOrder::create([
            'customer_id' => $this->customer->id,
            'round_count' => 3,
            'flat_count' => 0,
            'frequency' => 'Weekly',
            'status' => 'Active',
            'next_order_date' => $nextDate->toDateString(),
            'target_day' => Carbon::THURSDAY,
            'payment_method' => 'Cash',
            'delivery_address' => $this->customer->address,
        ]);

        // Generate due order
        $order = $recurringService->generateOrder($recurring, $nextDate);
        $this->assertNotNull($order);
        $this->assertEquals($recurring->id, $order->recurring_order_id);

        // Recurring schedule was advanced to next week
        $recurring->refresh();
        $this->assertEquals('2026-10-08', $recurring->next_order_date->toDateString());

        // Assign and fail delivery
        $delivery = $this->deliveryService->assignRiderToOrder($order, $this->riderA);
        $this->deliveryService->failDelivery($delivery, 'Customer unavailable', 'Not home on Oct 1', $this->riderUserA);

        // Retry the delivery
        $this->deliveryService->retryDelivery($delivery, $this->riderB, '2026-10-02', null, 99, $this->admin);

        // Verify recurring schedule is UNTOUCHED
        $recurring->refresh();
        $this->assertEquals('2026-10-08', $recurring->next_order_date->toDateString());

        // Verify only 1 order exists for this recurring schedule
        $this->assertEquals(1, Order::where('recurring_order_id', $recurring->id)->count());
    }

    /**
     * Test 10: Authorization enforcement - Customer and Rider cannot retry or cancel.
     */
    public function test_authorization_enforcement_non_admin_cannot_retry_or_cancel(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $this->deliveryService->failDelivery($delivery, 'Customer unavailable', 'Not home', $this->riderUserA);

        // Customer cannot retry
        $this->actingAs($this->customerUser)
            ->post(route('deliveries.retry', $delivery), [
                'rider_id' => $this->riderA->id,
                'delivery_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        // Customer cannot cancel
        $this->actingAs($this->customerUser)
            ->post(route('deliveries.cancel', $delivery), [
                'reason' => 'Customer cancel attempt',
            ])
            ->assertForbidden();

        // Rider cannot retry
        $this->actingAs($this->riderUserA)
            ->post(route('deliveries.retry', $delivery), [
                'rider_id' => $this->riderA->id,
                'delivery_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        // Rider cannot cancel
        $this->actingAs($this->riderUserA)
            ->post(route('deliveries.cancel', $delivery), [
                'reason' => 'Rider cancel attempt',
            ])
            ->assertForbidden();
    }

    /**
     * Test 11: Invalid status transitions are rejected.
     */
    public function test_invalid_status_transitions_are_rejected(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);

        // Cannot complete from Assigned without going En Route
        $this->expectException(DomainException::class);
        $this->deliveryService->completeDelivery($delivery);
    }

    /**
     * Test 12: Delivered delivery cannot be marked Failed.
     */
    public function test_delivered_delivery_cannot_be_marked_failed(): void
    {
        $delivery = $this->createAssignedDelivery($this->riderA);
        $this->deliveryService->startDelivery($delivery);
        $this->deliveryService->completeDelivery($delivery);

        $this->expectException(DomainException::class);
        $this->deliveryService->failDelivery($delivery, 'Customer unavailable');
    }
}
