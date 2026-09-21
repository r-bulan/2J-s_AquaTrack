<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use App\Services\CustomerSyncService;
use Tests\TestCase;

class CreditLedgerSyncWorkflowTest extends TestCase
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

    public function test_credit_order_delivery_correctly_updates_customer_credit_balance(): void
    {
        // Check baseline credit
        $initialCredit = (float) ($this->customer->creditLedger?->amount_owed ?? 0);

        // Create an order via Credit payment method
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
            'payment_status' => 'Credit',
            'payment_method' => 'Credit',
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
            'status' => 'Assigned',
            'jug_count' => 4,
            'gallon_type' => 'Round',
        ]);

        // Start delivery
        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/start");

        // Complete delivery
        $response = $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete", [
            'notes' => 'Delivered on credit terms',
        ]);
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $creditLedger = $this->customer->creditLedger;

        $expectedBalance = $initialCredit + 140.00;
        $this->assertEquals($expectedBalance, (float) $creditLedger->amount_owed);
        $this->assertEquals('Outstanding', $creditLedger->status);

        $order->refresh();
        $this->assertNotNull($order->synced_at, 'Order must be marked with synced_at timestamp');
    }

    public function test_non_credit_order_delivery_does_not_increase_customer_credit_balance(): void
    {
        $initialCredit = (float) ($this->customer->creditLedger?->amount_owed ?? 0);

        // Cash order
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Phone',
            'jug_count' => 2,
            'gallon_type' => 'Flat',
            'unit_price' => 40.00,
            'total_amount' => 80.00,
            'payment_status' => 'Unpaid',
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
            'status' => 'Assigned',
            'jug_count' => 2,
            'gallon_type' => 'Flat',
        ]);

        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/start");
        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete", [
            'notes' => 'Cash collected',
        ]);

        $this->customer->refresh();
        $this->assertEquals($initialCredit, (float) ($this->customer->creditLedger?->amount_owed ?? 0), 'Cash delivery must not affect credit ledger');
    }

    public function test_repeated_sync_execution_does_not_duplicate_credit_balance(): void
    {
        $initialCredit = (float) ($this->customer->creditLedger?->amount_owed ?? 0);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Phone',
            'jug_count' => 3,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 105.00,
            'payment_status' => 'Credit',
            'payment_method' => 'Credit',
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

        // Complete delivery normally once
        $this->actingAs($this->riderUser)->post("/deliveries/{$delivery->id}/complete", ['notes' => 'First sync']);

        $this->customer->refresh();
        $balanceAfterFirst = (float) $this->customer->creditLedger->amount_owed;
        $this->assertEquals($initialCredit + 105.00, $balanceAfterFirst);

        // Manually trigger CustomerSyncService again on same order to test service idempotency
        $order->refresh();
        app(CustomerSyncService::class)->syncDeliveredOrder($order);

        $this->customer->refresh();
        $this->assertEquals($balanceAfterFirst, (float) $this->customer->creditLedger->amount_owed, 'Balance must remain identical after repeated sync attempt');
    }

    public function test_customer_portal_and_admin_views_display_correct_credit_balance(): void
    {
        $this->customer->refresh();
        $owed = (float) $this->customer->creditLedger->amount_owed;

        // Verify Customer Portal reflects exact balance
        $portalResponse = $this->actingAs($this->customerUser)->get('/portal');
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee(number_format($owed, 2));

        // Verify Admin Customers page reflects exact balance
        $admin = User::where('role', 'admin')->first();
        $adminResponse = $this->actingAs($admin)->get('/customers');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee($this->customer->name);
    }
}
