<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    public function test_api_login_returns_token_and_user_profile(): void
    {
        $user = User::where('email', 'user@secureauth.com')->first();
        $user->forceFill(['two_factor_confirmed_at' => null])->save();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@secureauth.com',
            'password' => 'User@SecureAuth2026!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'token',
            'user' => ['id', 'name', 'email', 'roles', 'is_admin'],
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_api_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@secureauth.com',
            'password' => 'InvalidPassword123!',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_authenticated_user_can_update_profile_name(): void
    {
        $user = User::where('email', 'admin@secureauth.com')->first();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/auth/profile', [
            'name' => 'Administrator Updated',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
        $this->assertEquals('Administrator Updated', $user->fresh()->name);

        // Revert back
        $user->update(['name' => 'Administrator']);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::where('email', 'user@secureauth.com')->first();

        // 1. Wrong current password fails
        $badResponse = $this->actingAs($user, 'sanctum')->postJson('/api/auth/password', [
            'current_password' => 'WrongPassword123!',
            'new_password' => 'NewSecuredPass2026!#',
            'new_password_confirmation' => 'NewSecuredPass2026!#',
        ]);
        $badResponse->assertStatus(422);

        // 2. Correct current password succeeds
        $goodResponse = $this->actingAs($user, 'sanctum')->postJson('/api/auth/password', [
            'current_password' => 'User@SecureAuth2026!',
            'new_password' => 'NewSecuredPass2026!#',
            'new_password_confirmation' => 'NewSecuredPass2026!#',
        ]);
        $goodResponse->assertStatus(200);
        $goodResponse->assertJsonFragment(['success' => true]);

        // Restore original password
        $restoreResponse = $this->actingAs($user, 'sanctum')->postJson('/api/auth/password', [
            'current_password' => 'NewSecuredPass2026!#',
            'new_password' => 'User@SecureAuth2026!',
            'new_password_confirmation' => 'User@SecureAuth2026!',
        ]);
        $restoreResponse->assertStatus(200);
    }

    public function test_email_otp_flow_dispatches_and_verifies_successfully(): void
    {
        $user = User::where('email', 'admin@secureauth.com')->first();
        // Ensure 2FA is active
        $user->forceFill([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ])->save();

        // 1. Initial login triggers 2FA
        $loginRes = $this->postJson('/api/auth/login', [
            'email' => 'admin@secureauth.com',
            'password' => 'Admin@SecureAuth2026!',
        ]);
        $loginRes->assertStatus(200);
        $tempToken = $loginRes->json('temp_token');
        $this->assertNotEmpty($tempToken);

        // 2. Dispatch Email OTP
        $otpRes = $this->postJson('/api/auth/send-email-otp', [
            'temp_token' => $tempToken,
        ]);
        $otpRes->assertStatus(200);
        $otpRes->assertJsonFragment(['success' => true]);
        $debugCode = $otpRes->json('debug_code');
        $this->assertNotEmpty($debugCode);

        // 3. Verify Email OTP code
        $verifyRes = $this->postJson('/api/auth/two-factor-challenge', [
            'temp_token' => $tempToken,
            'code' => $debugCode,
        ]);
        $verifyRes->assertStatus(200);
        $verifyRes->assertJsonStructure(['success', 'token', 'user']);
        $this->assertTrue($verifyRes->json('success'));
    }
}
