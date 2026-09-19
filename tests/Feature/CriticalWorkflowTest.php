<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CriticalWorkflowTest extends TestCase
{
    public function test_full_refill_lifecycle_from_order_to_delivery_and_synchronization(): void
    {
        \App\Models\Setting::set('round_gallon_price', '35.00');
        \App\Models\Setting::set('flat_gallon_price', '40.00');

        $admin = User::where('role', 'admin')->first();
        $riderUser = User::where('role', 'rider')->first();
        $rider = $riderUser->rider;
        $customerUser = User::where('role', 'customer')->first();
        $customer = $customerUser->customer;

        // Step 1: Customer places order via Portal
        $orderData = [
            'gallon_type' => 'Flat',
            'jug_count' => 3,
            'payment_method' => 'GCash',
            'delivery_address' => $customer->address,
            'preferred_time' => '10:00 AM',
            'notes' => 'Please knock loudly',
        ];

        $response = $this->actingAs($customerUser)->post('/portal/orders', $orderData);
        $response->assertRedirect('/portal?tab=orders');

        $order = Order::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('Pending', $order->status);
        $this->assertEquals('Flat', $order->gallon_type);
        $this->assertEquals(3, $order->jug_count);
        $this->assertEquals(40.00, (float) $order->unit_price);
        $this->assertEquals(120.00, (float) $order->total_amount);

        // Step 2: Admin views order and assigns Rider
        $assignResponse = $this->actingAs($admin)->post("/orders/{$order->id}/assign-rider", [
            'rider_id' => $rider->id,
            'route_order' => 1,
        ]);
        $assignResponse->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('Confirmed', $order->status);
        $this->assertEquals($rider->id, $order->rider_id);

        $delivery = Delivery::where('order_id', $order->id)->first();
        $this->assertNotNull($delivery);
        $this->assertEquals('Assigned', $delivery->status);
        $this->assertEquals($rider->id, $delivery->rider_id);

        // Step 3: Rider starts delivery -> En Route
        $startResponse = $this->actingAs($riderUser)->post("/deliveries/{$delivery->id}/start");
        $startResponse->assertSessionHas('success');

        $delivery->refresh();
        $order->refresh();
        $this->assertEquals('En Route', $delivery->status);
        $this->assertEquals('Out for Delivery', $order->status);

        // Step 4: Rider completes delivery with proof of delivery
        $dummyImage = UploadedFile::fake()->create('pod_photo.jpg', 50, 'image/jpeg');
        $dummySignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $completeResponse = $this->actingAs($riderUser)->post("/deliveries/{$delivery->id}/complete", [
            'proof_photo' => $dummyImage,
            'signature' => $dummySignature,
            'notes' => 'Received with thanks',
        ]);
        $completeResponse->assertSessionHas('success');

        // Step 5: Verify System Business Synchronization
        $delivery->refresh();
        $order->refresh();
        $this->assertEquals('Delivered', $delivery->status);
        $this->assertEquals('Delivered', $order->status);
        $this->assertNotNull($delivery->delivery_date);
        $this->assertNotNull($delivery->proof_photo);
        $this->assertNotNull($delivery->signature);

        // Verify Automatic Idempotent Income Transaction
        $waterSales = Transaction::where('order_id', $order->id)
            ->where('category', 'Water Sales')
            ->get();

        $this->assertCount(1, $waterSales, 'Exactly one Water Sales transaction should be recorded for this order');
        $this->assertEquals(120.00, (float) $waterSales->first()->amount);

        // Idempotency check: trigger complete again or call revenue service again, should NOT create duplicate
        app(\App\Services\RevenueService::class)->recordOrderDeliveredIncome($order);
        $this->assertCount(1, Transaction::where('order_id', $order->id)->where('category', 'Water Sales')->get());

        // Verify Customer record synchronization
        $customer->refresh();
        $this->assertEquals(now()->toDateString(), $customer->last_order_date->toDateString());
    }
}
