<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DeliveryWorkflowSequenceTest extends TestCase
{
    protected Rider $rider1;
    protected Rider $rider2;
    protected User $riderUser1;
    protected User $riderUser2;
    protected User $customerUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->riderUser1 = User::where('email', 'rider@twojs.test')->first();
        $this->rider1 = $this->riderUser1->rider;

        $this->riderUser2 = User::where('email', 'arnel.rider@twojs.test')->first();
        $this->rider2 = $this->riderUser2->rider;

        $this->customerUser = User::where('role', 'customer')->first();
        $this->customer = $this->customerUser->customer;
    }

    protected function createAssignedDelivery(Rider $rider): Delivery
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'order_date' => now()->toDateString(),
            'status' => 'Confirmed',
            'type' => 'Walk-in',
            'jug_count' => 2,
            'gallon_type' => 'Round',
            'unit_price' => 35.00,
            'total_amount' => 70.00,
            'payment_status' => 'Unpaid',
            'payment_method' => 'Cash',
            'delivery_address' => 'Test Delivery Address',
            'rider_id' => $rider->id,
            'rider_name' => $rider->name,
            'route_order' => 1,
        ]);

        return Delivery::create([
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => '0912-345-6789',
            'address' => $order->delivery_address,
            'area' => 'Zone 1',
            'rider_id' => $rider->id,
            'rider_name' => $rider->name,
            'route_order' => 1,
            'status' => 'Assigned',
            'jug_count' => 2,
            'gallon_type' => 'Round',
        ]);
    }

    public function test_assigned_delivery_cannot_be_marked_delivered_directly(): void
    {
        $delivery = $this->createAssignedDelivery($this->rider1);

        $response = $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/complete", [
            'notes' => 'Attempting skip to complete',
        ]);

        $response->assertSessionHasErrors('error');

        $delivery->refresh();
        $this->assertEquals('Assigned', $delivery->status, 'Status must remain Assigned when skip attempt is made');
        $this->assertNull($delivery->delivery_date);
    }

    public function test_assigned_delivery_can_transition_to_en_route(): void
    {
        $delivery = $this->createAssignedDelivery($this->rider1);

        $response = $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/start");
        $response->assertSessionHas('success');

        $delivery->refresh();
        $this->assertEquals('En Route', $delivery->status);
        $this->assertEquals('Out for Delivery', $delivery->order->status);
    }

    public function test_en_route_delivery_can_transition_to_delivered(): void
    {
        $delivery = $this->createAssignedDelivery($this->rider1);

        // Transition to En Route first
        $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/start");

        $dummyPhoto = UploadedFile::fake()->create('pod.jpg', 50, 'image/jpeg');

        // Complete delivery
        $response = $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/complete", [
            'proof_photo' => $dummyPhoto,
            'returned_jugs' => 2,
            'notes' => 'Delivered smoothly',
        ]);

        $response->assertSessionHas('success');

        $delivery->refresh();
        $this->assertEquals('Delivered', $delivery->status);
        $this->assertEquals('Delivered', $delivery->order->status);
        $this->assertEquals(2, $delivery->returned_jugs);
        $this->assertNotNull($delivery->delivery_date);
    }

    public function test_delivered_delivery_cannot_be_completed_again(): void
    {
        $delivery = $this->createAssignedDelivery($this->rider1);

        // Start and complete once
        $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/start");
        $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/complete", ['notes' => 'First completion']);

        $delivery->refresh();
        $this->assertEquals('Delivered', $delivery->status);

        // Attempt second completion
        $secondResponse = $this->actingAs($this->riderUser1)->post("/deliveries/{$delivery->id}/complete", ['notes' => 'Second duplicate completion']);
        $secondResponse->assertSessionHasErrors('error');
    }

    public function test_rider_cannot_modify_another_riders_delivery(): void
    {
        // Delivery assigned to Rider 1
        $delivery = $this->createAssignedDelivery($this->rider1);

        // Rider 2 attempts to start Rider 1's delivery
        $response = $this->actingAs($this->riderUser2)->post("/deliveries/{$delivery->id}/start");
        $response->assertStatus(403);

        // Rider 2 attempts to complete Rider 1's delivery
        $completeResponse = $this->actingAs($this->riderUser2)->post("/deliveries/{$delivery->id}/complete", ['notes' => 'Hijack']);
        $completeResponse->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_complete_delivery(): void
    {
        $delivery = $this->createAssignedDelivery($this->rider1);

        // Customer attempts to complete delivery
        $response = $this->actingAs($this->customerUser)->post("/deliveries/{$delivery->id}/complete", ['notes' => 'Customer tampering']);
        $response->assertStatus(403);
    }
}
