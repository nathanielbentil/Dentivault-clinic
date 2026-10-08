<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    public function test_api_login_returns_token_and_user_profile(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@secureauth.com',
            'password' => 'Admin@SecureAuth2026!',
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

    public function test_authenticated_user_can_access_user_profile(): void
    {
        $user = User::where('email', 'user@secureauth.com')->first();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/auth/user');
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'user@secureauth.com']);
    }
}
