<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\PricingService;
use Tests\TestCase;

class PriceTest extends TestCase
{
    public function test_pricing_calculation_and_historical_price_preservation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $pricingService = app(PricingService::class);

        // 1. Initial prices
        $pricingService->updatePrices(35.00, 40.00);
        $this->assertEquals(70.00, $pricingService->calculateTotal('Round', 2));
        $this->assertEquals(80.00, $pricingService->calculateTotal('Flat', 2));

        // 2. Create Order A with Round price ₱35
        $orderA = app(\App\Services\OrderService::class)->createOrder([
            'customer_id' => $customer->id,
            'type' => 'Phone',
            'gallon_type' => 'Round',
            'jug_count' => 2,
            'payment_method' => 'Cash',
        ]);

        $this->assertEquals(35.00, (float) $orderA->unit_price);
        $this->assertEquals(70.00, (float) $orderA->total_amount);

        // 3. Admin changes Round price to ₱45.00
        $response = $this->actingAs($admin)->post('/settings', [
            'round_gallon_price' => 45.00,
            'flat_gallon_price' => 50.00,
        ]);
        $response->assertSessionHas('success');

        $this->assertEquals(45.00, $pricingService->getPrice('Round'));

        // 4. Create Order B with new Round price
        $orderB = app(\App\Services\OrderService::class)->createOrder([
            'customer_id' => $customer->id,
            'type' => 'Phone',
            'gallon_type' => 'Round',
            'jug_count' => 2,
            'payment_method' => 'Cash',
        ]);

        $this->assertEquals(45.00, (float) $orderB->unit_price);
        $this->assertEquals(90.00, (float) $orderB->total_amount);

        // 5. Verify Order A still contains its original unit price and total!
        $orderAFresh = Order::find($orderA->id);
        $this->assertEquals(35.00, (float) $orderAFresh->unit_price);
        $this->assertEquals(70.00, (float) $orderAFresh->total_amount);
    }
}
