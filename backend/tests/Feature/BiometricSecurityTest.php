<?php

namespace Tests\Feature;

use App\Models\BiometricCredential;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BiometricSecurityTest extends TestCase
{
    public function test_authenticated_staff_can_request_biometric_registration_challenge(): void
    {
        $user = User::where('email', 'dentist@dentivault.com')->first();

        $response = $this->actingAs($user)->getJson('/biometric/register-challenge');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'challenge',
            'rp' => ['name', 'id'],
            'user' => ['id', 'name', 'displayName'],
            'pubKeyCredParams',
            'authenticatorSelection',
        ]);

        $this->assertNotEmpty(session('biometric_register_challenge'));
    }

    public function test_staff_can_complete_biometric_registration(): void
    {
        $user = User::where('email', 'dentist@dentivault.com')->first();
        $dummyChallenge = 'test-challenge-base64-random-token-123456';
        session(['biometric_register_challenge' => $dummyChallenge]);

        $credentialId = 'test_cred_id_' . bin2hex(random_bytes(8));

        $response = $this->actingAs($user)->postJson('/biometric/register-verify', [
            'id' => $credentialId,
            'rawId' => base64_encode($credentialId),
            'name' => 'Dr. Isaac Windows Hello Sensor',
            'response' => [
                'clientDataJSON' => base64_encode('{"type":"webauthn.create"}'),
                'attestationObject' => base64_encode('dummy-attestation-bytes'),
            ],
            'transports' => ['internal'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('biometric_credentials', [
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'name' => 'Dr. Isaac Windows Hello Sensor',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'auth.biometric_registered',
            'user_id' => $user->id,
        ]);
    }

    public function test_biometric_login_challenge_generation(): void
    {
        $response = $this->getJson('/biometric/login-challenge');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'challenge',
            'rpId',
            'timeout',
            'userVerification',
        ]);

        $this->assertNotEmpty(session('biometric_login_challenge'));
    }

    public function test_biometric_1_touch_login_flow(): void
    {
        $admin = User::where('email', 'admin@dentivault.com')->first();
        $credentialId = 'admin_fingerprint_key_' . bin2hex(random_bytes(8));

        $cred = BiometricCredential::create([
            'user_id' => $admin->id,
            'name' => 'Dr. Prof. Nathaniel Touch ID',
            'credential_id' => $credentialId,
            'public_key' => json_encode(['dummy' => 'key']),
            'sign_count' => 0,
            'transports' => json_encode(['internal']),
        ]);

        session(['biometric_login_challenge' => 'dummy-login-challenge']);

        $response = $this->postJson('/biometric/login-verify', [
            'id' => $credentialId,
            'rawId' => base64_encode($credentialId),
            'response' => [
                'clientDataJSON' => base64_encode('{"type":"webauthn.get"}'),
                'authenticatorData' => base64_encode('auth-data'),
                'signature' => base64_encode('signature-bytes'),
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
        $this->assertEquals($admin->id, Auth::id());
        $this->assertEquals(1, $cred->fresh()->sign_count);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'auth.biometric_login',
            'user_id' => $admin->id,
        ]);
    }

    public function test_staff_can_revoke_biometric_credential(): void
    {
        $dentist = User::where('email', 'dentist@dentivault.com')->first();
        $cred = BiometricCredential::create([
            'user_id' => $dentist->id,
            'name' => 'Old Revoked Tablet Sensor',
            'credential_id' => 'revoked_key_' . bin2hex(random_bytes(8)),
            'public_key' => json_encode(['dummy' => 'key']),
            'sign_count' => 0,
        ]);

        $this->assertDatabaseHas('biometric_credentials', ['id' => $cred->id]);

        $response = $this->actingAs($dentist)->delete("/biometric/credentials/{$cred->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('biometric_credentials', ['id' => $cred->id]);
    }
}
