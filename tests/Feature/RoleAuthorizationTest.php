<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    public function test_customer_is_forbidden_from_admin_and_rider_routes(): void
    {
        $customer = User::where('role', 'customer')->first();

        $forbiddenUrls = [
            '/dashboard',
            '/orders',
            '/customers',
            '/deliveries',
            '/inventory',
            '/finance',
            '/maintenance',
            '/reports',
            '/riders',
            '/settings',
            '/activity-logs',
        ];

        foreach ($forbiddenUrls as $url) {
            $response = $this->actingAs($customer)->get($url);
            $response->assertStatus(403);
        }
    }

    public function test_rider_is_forbidden_from_admin_only_routes(): void
    {
        $rider = User::where('role', 'rider')->first();

        $forbiddenUrls = [
            '/dashboard',
            '/orders',
            '/inventory',
            '/finance',
            '/maintenance',
            '/reports',
            '/riders',
            '/settings',
            '/activity-logs',
        ];

        foreach ($forbiddenUrls as $url) {
            $response = $this->actingAs($rider)->get($url);
            $response->assertStatus(403);
        }
    }

    public function test_admin_can_access_all_management_modules(): void
    {
        $admin = User::where('role', 'admin')->first();

        $accessibleUrls = [
            '/dashboard',
            '/orders',
            '/customers',
            '/deliveries',
            '/inventory',
            '/finance',
            '/maintenance',
            '/reports',
            '/riders',
            '/settings',
            '/activity-logs',
        ];

        foreach ($accessibleUrls as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertStatus(200);
        }
    }
}
