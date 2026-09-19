<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_admin_is_redirected_to_dashboard(): void
    {
        $admin = User::where('role', 'admin')->first();
        $response = $this->actingAs($admin)->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_rider_is_redirected_to_deliveries(): void
    {
        $rider = User::where('role', 'rider')->first();
        $response = $this->actingAs($rider)->get('/');
        $response->assertRedirect('/deliveries');
    }

    public function test_customer_is_redirected_to_portal(): void
    {
        $customer = User::where('role', 'customer')->first();
        $response = $this->actingAs($customer)->get('/');
        $response->assertRedirect('/portal');
    }
}
