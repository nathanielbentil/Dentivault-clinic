<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RbacSecurityTest extends TestCase
{
    public function test_seeded_users_and_roles_exist(): void
    {
        $admin = User::where('email', 'admin@dentivault.com')->first();
        $user = User::where('email', 'dentist@dentivault.com')->first();

        $this->assertNotNull($admin, 'Administrator account must exist in database.');
        $this->assertNotNull($user, 'Dentist account must exist in database.');

        $this->assertTrue($admin->hasRole('Admin') || $admin->hasRole('SuperAdmin'));
        $this->assertTrue($user->hasRole('Dentist') || $user->hasRole('User'));
        $this->assertFalse($user->hasRole('Admin'));
    }

    public function test_admin_can_access_admin_api_endpoints(): void
    {
        $admin = User::where('email', 'admin@dentivault.com')->first();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'stats', 'users', 'roles']);
    }

    public function test_standard_user_is_forbidden_from_admin_api_endpoints(): void
    {
        $user = User::where('email', 'dentist@dentivault.com')->first();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/admin/users');
        $response->assertStatus(403);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_admin_can_delete_staff_account_and_records_audit_trail(): void
    {
        $admin = User::where('email', 'admin@dentivault.com')->first();

        // 1. Create a temporary staff user to test deletion
        $tempUser = User::create([
            'name' => 'Dr. Temp DeleteMe',
            'email' => 'temp.dentist@dentivault.com',
            'password' => 'TempPassword2026!#',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'temp.dentist@dentivault.com']);

        // 2. Admin deletes user
        $response = $this->actingAs($admin)->delete("/admin/users/{$tempUser->id}");
        $response->assertRedirect();

        // 3. User is purged from database
        $this->assertDatabaseMissing('users', ['email' => 'temp.dentist@dentivault.com']);

        // 4. Immutable audit trail recorded
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user.deleted',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::where('email', 'admin@dentivault.com')->first();

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");
        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_all_staff_can_update_own_email_address(): void
    {
        $dentist = User::where('email', 'dentist@dentivault.com')->first();

        // 1. Wrong current password fails
        $badResponse = $this->actingAs($dentist)->post('/profile/update-email', [
            'email' => 'new.dentist@dentivault.com',
            'current_password' => 'WrongPassword123!',
        ]);
        $badResponse->assertSessionHasErrors(['current_password']);

        // 2. Correct current password succeeds
        $goodResponse = $this->actingAs($dentist)->post('/profile/update-email', [
            'email' => 'updated.dentist@dentivault.com',
            'current_password' => 'Dentist@Dental2026!',
        ]);
        $goodResponse->assertRedirect();
        $goodResponse->assertSessionHas('status');

        $this->assertEquals('updated.dentist@dentivault.com', $dentist->fresh()->email);

        // 3. Restore original email
        $restoreResponse = $this->actingAs($dentist)->post('/profile/update-email', [
            'email' => 'dentist@dentivault.com',
            'current_password' => 'Dentist@Dental2026!',
        ]);
        $restoreResponse->assertRedirect();
        $this->assertEquals('dentist@dentivault.com', $dentist->fresh()->email);
    }
}
