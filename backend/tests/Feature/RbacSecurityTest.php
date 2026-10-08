<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RbacSecurityTest extends TestCase
{
    public function test_seeded_users_and_roles_exist(): void
    {
        $admin = User::where('email', 'admin@secureauth.com')->first();
        $user = User::where('email', 'user@secureauth.com')->first();

        $this->assertNotNull($admin, 'Administrator account must exist in database.');
        $this->assertNotNull($user, 'Standard user account must exist in database.');

        $this->assertTrue($admin->hasRole('Admin') || $admin->hasRole('SuperAdmin'));
        $this->assertTrue($user->hasRole('User'));
        $this->assertFalse($user->hasRole('Admin'));
    }

    public function test_admin_can_access_admin_api_endpoints(): void
    {
        $admin = User::where('email', 'admin@secureauth.com')->first();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'stats', 'users', 'roles']);
    }

    public function test_standard_user_is_forbidden_from_admin_api_endpoints(): void
    {
        $user = User::where('email', 'user@secureauth.com')->first();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/users');
        $response->assertStatus(403);
        $response->assertJsonFragment(['success' => false]);
    }
}
