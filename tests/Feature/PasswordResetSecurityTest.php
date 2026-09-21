<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    protected function getTestUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'reset_test_runner@twojs.test'],
            [
                'name' => 'Password Reset Test Runner',
                'password' => Hash::make('InitialTestPassword123!'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]
        );
    }

    public function test_forgot_password_request_returns_generic_response_for_existing_user(): void
    {
        $user = $this->getTestUser();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status', 'If an account exists with that email address, a password reset link has been dispatched.');

        // Verify token was stored in password_reset_tokens table
        $tokenRecord = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($tokenRecord, 'A reset token should be recorded for the valid customer');
    }

    public function test_forgot_password_request_returns_same_generic_response_for_non_existent_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent_user_999@example.com',
        ]);

        $response->assertSessionHas('status', 'If an account exists with that email address, a password reset link has been dispatched.');

        // No token created for non-existent email
        $tokenRecord = DB::table('password_reset_tokens')->where('email', 'nonexistent_user_999@example.com')->first();
        $this->assertNull($tokenRecord);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->getTestUser();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));

        // Token must be invalidated/removed after successful reset
        $this->assertFalse(Password::tokenExists($user, $token), 'Token should be invalidated after use');
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = $this->getTestUser();
        $initialPassword = $user->password;

        $response = $this->post('/reset-password', [
            'token' => 'invalid-forged-token-xyz',
            'email' => $user->email,
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ]);

        $response->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertEquals($initialPassword, $user->password, 'Password must not change with invalid token');
    }

    public function test_password_reset_fails_with_expired_token(): void
    {
        $user = $this->getTestUser();
        $token = Password::createToken($user);

        // Manually expire the token by setting created_at to 2 hours ago (expiry is 60 minutes)
        DB::table('password_reset_tokens')->where('email', $user->email)->update([
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ExpiredPassword123!',
            'password_confirmation' => 'ExpiredPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_fails_with_invalid_confirmation(): void
    {
        $user = $this->getTestUser();
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'MismatchPassword999!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_reset_token_cannot_be_reused_after_successful_reset(): void
    {
        $user = $this->getTestUser();
        $token = Password::createToken($user);

        // First reset: should succeed
        $firstResponse = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ResetPasswordOnce123!',
            'password_confirmation' => 'ResetPasswordOnce123!',
        ]);
        $firstResponse->assertRedirect('/login');

        // Second reset using same token: must fail
        $secondResponse = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ResetPasswordTwice123!',
            'password_confirmation' => 'ResetPasswordTwice123!',
        ]);
        $secondResponse->assertSessionHasErrors('email');
    }
}
