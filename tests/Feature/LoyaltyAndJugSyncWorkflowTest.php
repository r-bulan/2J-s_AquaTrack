<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerSyncService;
use Tests\TestCase;

class LoyaltyAndJugSyncWorkflowTest extends TestCase
{
    protected Rider $rider;
    protected User $riderUser;
    protected User $customerUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->riderUser = User::where('email', 'rider@twojs.test')->first();
        $this->rider = $this->riderUser->rider;

        $this->customerUser = User::where('role', 'customer')->first();
        $this->customer = $this->customerUser->customer;
    }

    public function test_eligible_completed_order_updates_loyalty_and_awards_free_jug(): void
    {
        Setting::set('loyalty_refills_needed', 10);

        $loyalty = $this->customer->loyaltyRecord;
        $loyalty->update([
            'refills_count' => 8,
            'refills_needed' => 10,
            'free_jugs_earned' => 0,
        ]);

        // Refill order of 3 jugs: 8 + 3 = 11 refills -> 1 free earned, 1 refill remaining
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Online',
            'jug_count' => 3,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 105.00,
            'payment_status' => 'Paid',
            'payment_method' => 'GCash',
            'delivery_address' => $this->customer->address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'address' => $order->delivery_address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
            'status' => 'En Route',
            'jug_count' => 3,
            'gallon_type' => 'Round',
        ]);

        $response = $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete", [
            'notes' => 'Delivered refills',
        ]);
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $updatedLoyalty = $this->customer->loyaltyRecord;

        $this->assertEquals(1, $updatedLoyalty->refills_count);
        $this->assertEquals(1, $updatedLoyalty->free_jugs_earned);
        $this->assertEquals(now()->toDateString(), $updatedLoyalty->last_reward_date->toDateString());
    }

    public function test_pending_or_cancelled_order_does_not_update_loyalty(): void
    {
        $this->customer->refresh();
        $initialRefills = $this->customer->loyaltyRecord->refills_count;

        // Pending order
        $pendingOrder = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Pending',
            'type' => 'Walk-in',
            'jug_count' => 5,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 175.00,
            'payment_status' => 'Unpaid',
            'payment_method' => 'Cash',
            'delivery_address' => $this->customer->address,
        ]);

        app(CustomerSyncService::class)->syncDeliveredOrder($pendingOrder);

        $this->customer->refresh();
        $this->assertEquals($initialRefills, $this->customer->loyaltyRecord->refills_count, 'Pending order must not update loyalty');

        // Cancelled order
        $pendingOrder->update(['status' => 'Cancelled']);
        app(CustomerSyncService::class)->syncDeliveredOrder($pendingOrder);

        $this->customer->refresh();
        $this->assertEquals($initialRefills, $this->customer->loyaltyRecord->refills_count, 'Cancelled order must not update loyalty');
    }

    public function test_repeated_delivery_completion_does_not_duplicate_loyalty(): void
    {
        Setting::set('loyalty_refills_needed', 10);

        $this->customer->loyaltyRecord->update([
            'refills_count' => 2,
            'free_jugs_earned' => 0,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Online',
            'jug_count' => 2,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 70.00,
            'payment_status' => 'Paid',
            'payment_method' => 'GCash',
            'delivery_address' => $this->customer->address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'address' => $order->delivery_address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
            'status' => 'En Route',
            'jug_count' => 2,
            'gallon_type' => 'Round',
        ]);

        // Complete delivery
        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete");

        $this->customer->refresh();
        $this->assertEquals(4, $this->customer->loyaltyRecord->refills_count);

        // Attempt second sync on same order
        $order->refresh();
        app(CustomerSyncService::class)->syncDeliveredOrder($order);

        $this->customer->refresh();
        $this->assertEquals(4, $this->customer->loyaltyRecord->refills_count, 'Loyalty refills must not be duplicated on repeated sync');
    }

    public function test_completed_delivery_updates_jug_balance_with_delivered_and_returned_jugs(): void
    {
        $this->customer->jugLedger->update(['jugs_held' => 5]);

        // Customer receives 4 jugs and returns 3 empty jugs: net change = +1
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Phone',
            'jug_count' => 4,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 140.00,
            'payment_status' => 'Paid',
            'payment_method' => 'Cash',
            'delivery_address' => $this->customer->address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'address' => $order->delivery_address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
            'status' => 'En Route',
            'jug_count' => 4,
            'gallon_type' => 'Round',
        ]);

        $response = $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete", [
            'returned_jugs' => 3,
            'notes' => 'Received 3 empty containers',
        ]);
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertEquals(6, $this->customer->jugLedger->jugs_held, 'Jugs held should be 5 initial + 4 delivered - 3 returned = 6');
    }

    public function test_repeated_delivery_completion_does_not_duplicate_jug_balance(): void
    {
        $this->customer->jugLedger->update(['jugs_held' => 2]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Phone',
            'jug_count' => 2,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 70.00,
            'payment_status' => 'Paid',
            'payment_method' => 'Cash',
            'delivery_address' => $this->customer->address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'address' => $order->delivery_address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
            'status' => 'En Route',
            'jug_count' => 2,
            'gallon_type' => 'Round',
        ]);

        // Complete delivery (2 delivered, 0 returned -> jugs held becomes 2 + 2 = 4)
        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete", [
            'returned_jugs' => 0,
        ]);

        $this->customer->refresh();
        $this->assertEquals(4, $this->customer->jugLedger->jugs_held);

        // Attempt duplicate sync
        $order->refresh();
        app(CustomerSyncService::class)->syncDeliveredOrder($order);

        $this->customer->refresh();
        $this->assertEquals(4, $this->customer->jugLedger->jugs_held, 'Jugs held must not duplicate on repeated sync');
    }

    public function test_owner_can_configure_loyalty_threshold_via_settings(): void
    {
        $admin = User::where('role', 'admin')->first();

        // Admin updates threshold from 10 to 5 refills
        $response = $this->actingAs($admin)->post('/settings', [
            'round_gallon_price' => 35.00,
            'flat_gallon_price' => 40.00,
            'loyalty_refills_needed' => 5,
        ]);
        $response->assertSessionHas('success');

        $this->assertEquals('5', Setting::get('loyalty_refills_needed'));

        // Customer has 4 refills, now receives 1 more refill -> reaches 5 and earns free jug
        $this->customer->loyaltyRecord->update([
            'refills_count' => 4,
            'free_jugs_earned' => 0,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Walk-in',
            'jug_count' => 1,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 35.00,
            'payment_status' => 'Paid',
            'payment_method' => 'Cash',
            'delivery_address' => $this->customer->address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
        ]);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'address' => $order->delivery_address,
            'rider_id' => $this->rider->id,
            'rider_name' => $this->rider->name,
            'route_order' => 1,
            'status' => 'En Route',
            'jug_count' => 1,
            'gallon_type' => 'Round',
        ]);

        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete");

        $this->customer->refresh();
        $this->assertEquals(0, $this->customer->loyaltyRecord->refills_count);
        $this->assertEquals(1, $this->customer->loyaltyRecord->free_jugs_earned);
        $this->assertEquals(5, $this->customer->loyaltyRecord->refills_needed);
    }
}
