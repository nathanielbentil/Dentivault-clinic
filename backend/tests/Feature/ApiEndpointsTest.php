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

    public function test_forgot_password_anti_user_enumeration(): void
    {
        // 1. Unregistered email receives generic message (anti-enumeration)
        $unregRes = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@secureauth.com',
        ]);
        $unregRes->assertStatus(200);
        $unregRes->assertJsonFragment(['success' => true]);
        $this->assertNull($unregRes->json('debug_code'));

        // 2. Registered user receives code
        $regRes = $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@secureauth.com',
        ]);
        $regRes->assertStatus(200);
        $regRes->assertJsonFragment(['success' => true]);
        $this->assertNotEmpty($regRes->json('debug_code'));
    }

    public function test_reset_password_flow_and_session_revocation(): void
    {
        $user = User::where('email', 'user@secureauth.com')->first();

        // 1. Create access token to test revocation
        $token = $user->createToken('test-session')->plainTextToken;
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

        // 2. Request forgot password code
        $forgotRes = $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@secureauth.com',
        ]);
        $code = $forgotRes->json('debug_code');

        // 3. Bad code fails
        $badReset = $this->postJson('/api/auth/reset-password', [
            'email' => 'user@secureauth.com',
            'code' => '000000',
            'password' => 'NewResetPass2026!#',
            'password_confirmation' => 'NewResetPass2026!#',
        ]);
        $badReset->assertStatus(422);

        // 4. Good code resets password and revokes all tokens
        $goodReset = $this->postJson('/api/auth/reset-password', [
            'email' => 'user@secureauth.com',
            'code' => $code,
            'password' => 'NewResetPass2026!#',
            'password_confirmation' => 'NewResetPass2026!#',
        ]);
        $goodReset->assertStatus(200);
        $goodReset->assertJsonFragment(['success' => true]);

        // Verify old token was revoked
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);

        // 5. Restore original user password
        $restoreForgot = $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@secureauth.com',
        ]);
        $restoreCode = $restoreForgot->json('debug_code');
        $this->postJson('/api/auth/reset-password', [
            'email' => 'user@secureauth.com',
            'code' => $restoreCode,
            'password' => 'User@SecureAuth2026!',
            'password_confirmation' => 'User@SecureAuth2026!',
        ]);
    }

    public function test_email_2fa_activation_flow(): void
    {
        $user = User::where('email', 'user@secureauth.com')->first();
        $user->forceFill(['two_factor_confirmed_at' => null])->save();

        // 1. Initiate Email 2FA setup
        $setupRes = $this->actingAs($user, 'sanctum')->postJson('/api/2fa/email-setup');
        $setupRes->assertStatus(200);
        $setupRes->assertJsonFragment(['success' => true]);
        $debugCode = $setupRes->json('debug_code');
        $this->assertNotEmpty($debugCode);

        // 2. Confirm Email 2FA setup
        $confirmRes = $this->actingAs($user, 'sanctum')->postJson('/api/2fa/email-confirm', [
            'code' => $debugCode,
        ]);
        $confirmRes->assertStatus(200);
        $confirmRes->assertJsonFragment(['success' => true]);
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }
}
