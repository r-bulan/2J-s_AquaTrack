<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use Tests\TestCase;

class ViewRenderingTest extends TestCase
{
    public function test_all_admin_views_render_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();

        $routes = [
            '/dashboard',
            '/orders',
            '/customers',
            '/deliveries',
            '/inventory',
            '/finance',
            '/maintenance',
            '/riders',
            '/feedback',
            '/reports',
            '/settings',
            '/activity-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            $response->assertStatus(200, "Route {$route} failed with status " . $response->status());
        }
    }

    public function test_all_rider_views_render_successfully(): void
    {
        $rider = User::where('role', 'rider')->first();

        $response = $this->actingAs($rider)->get('/deliveries');
        $response->assertStatus(200);
    }

    public function test_all_customer_views_render_successfully(): void
    {
        $customer = User::where('role', 'customer')->first();

        $response = $this->actingAs($customer)->get('/portal');
        $response->assertStatus(200);

        $response = $this->actingAs($customer)->get('/portal?tab=orders');
        $response->assertStatus(200);

        $response = $this->actingAs($customer)->get('/portal?tab=feedback');
        $response->assertStatus(200);
    }

    public function test_guest_auth_views_render_successfully(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
        $this->get('/forgot-password')->assertStatus(200);
    }
}
