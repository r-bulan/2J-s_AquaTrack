<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationWorkflowTest extends TestCase
{
    public function test_customer_registration_leaves_email_unverified_and_dispatches_notification(): void
    {
        Event::fake([Registered::class]);

        $regData = [
            'name' => 'Unverified Customer Test',
            'email' => 'unverified_' . time() . '@example.com',
            'phone' => '0912-345-6789',
            'address' => '123 Sampaguita St.',
            'barangay' => 'San Antonio',
            'area' => 'Zone 1',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->post('/register', $regData);
        $response->assertRedirect('/portal');

        $user = User::where('email', $regData['email'])->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at, 'Newly registered customer must have null email_verified_at');
        $this->assertFalse($user->hasVerifiedEmail());

        Event::assertDispatched(Registered::class);
    }

    public function test_admin_created_rider_is_automatically_pre_verified(): void
    {
        $admin = User::where('role', 'admin')->first();

        $riderData = [
            'name' => 'Auto Verified Rider Test',
            'email' => 'verified_rider_' . time() . '@twojs.test',
            'phone' => '0919-888-7766',
            'password' => 'Password123!',
            'wage_rate' => 450.00,
            'cash_advance' => 0.00,
            'status' => 'Active',
            'vehicle' => 'Honda TMX 125',
        ];

        $response = $this->actingAs($admin)->post('/riders', $riderData);
        $response->assertRedirect('/riders');

        $riderUser = User::where('email', $riderData['email'])->first();
        $this->assertNotNull($riderUser);
        $this->assertNotNull($riderUser->email_verified_at, 'Admin-created Rider must be pre-verified');
        $this->assertTrue($riderUser->hasVerifiedEmail());
    }

    public function test_unverified_customer_can_view_portal_home_with_notice_banner(): void
    {
        $unverifiedUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);
        $customer = Customer::create([
            'user_id' => $unverifiedUser->id,
            'name' => $unverifiedUser->name,
            'email' => $unverifiedUser->email,
            'phone' => '0912-111-2222',
            'address' => 'Customer Street',
            'status' => 'Active',
        ]);
        $customer->jugLedger()->create(['customer_name' => $customer->name]);
        $customer->creditLedger()->create(['customer_name' => $customer->name]);
        $customer->loyaltyRecord()->create(['customer_name' => $customer->name]);

        $response = $this->actingAs($unverifiedUser)->get('/portal');
        $response->assertStatus(200);
        $response->assertSee('Email Verification Pending');
    }

    public function test_unverified_customer_cannot_place_order_and_is_redirected_to_verify_notice(): void
    {
        $unverifiedUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);
        $customer = Customer::create([
            'user_id' => $unverifiedUser->id,
            'name' => $unverifiedUser->name,
            'email' => $unverifiedUser->email,
            'phone' => '0912-111-2222',
            'address' => 'Customer Street',
            'status' => 'Active',
        ]);
        $customer->jugLedger()->create(['customer_name' => $customer->name]);
        $customer->creditLedger()->create(['customer_name' => $customer->name]);
        $customer->loyaltyRecord()->create(['customer_name' => $customer->name]);

        $orderData = [
            'gallon_type' => 'Round',
            'jug_count' => 2,
            'payment_method' => 'Cash',
            'delivery_address' => 'Sample Address',
        ];

        $response = $this->actingAs($unverifiedUser)->post('/portal/orders', $orderData);
        $response->assertRedirect('/email/verify');
    }

    public function test_customer_can_verify_email_with_valid_signed_url(): void
    {
        Event::fake([Verified::class]);

        $unverifiedUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $unverifiedUser->id, 'hash' => sha1($unverifiedUser->getEmailForVerification())]
        );

        $response = $this->actingAs($unverifiedUser)->get($verificationUrl);
        $response->assertRedirect('/portal');
        $response->assertSessionHas('success');

        $unverifiedUser->refresh();
        $this->assertNotNull($unverifiedUser->email_verified_at);
        $this->assertTrue($unverifiedUser->hasVerifiedEmail());

        Event::assertDispatched(Verified::class);
    }

    public function test_verification_fails_with_tampered_signature(): void
    {
        $unverifiedUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $tamperedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $unverifiedUser->id, 'hash' => 'wrong-tampered-hash-value']
        );

        $response = $this->actingAs($unverifiedUser)->get($tamperedUrl);
        $response->assertStatus(403);

        $unverifiedUser->refresh();
        $this->assertNull($unverifiedUser->email_verified_at);
    }

    public function test_customer_can_resend_verification_notification(): void
    {
        $unverifiedUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($unverifiedUser)->post('/email/verification-notification');
        $response->assertSessionHas('status', 'verification-link-sent');
    }
}
