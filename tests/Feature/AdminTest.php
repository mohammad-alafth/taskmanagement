<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat16_non_admin_cannot_access_admin_pages(): void
    {
        $manager = $this->makeUser('manager');
        $staff = $this->makeUser('staff');
        $checker = $this->makeUser('checker');

        foreach ([$manager, $staff, $checker] as $user) {
            $this->actingAs($user)->get('/admin/users')->assertForbidden();
            $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
            $this->actingAs($user)->get('/admin/roles')->assertForbidden();
        }
    }

    public function test_admin_can_view_user_list_create_edit_and_roles_pages(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('staff');

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee($target->email);
        $this->actingAs($admin)->get('/admin/users/create')->assertOk();
        $this->actingAs($admin)->get("/admin/users/{$target->id}/edit")->assertOk()->assertSee($target->email);
        $this->actingAs($admin)->get('/admin/roles')->assertOk()->assertSee('Create task');
    }

    public function test_admin_creates_user_with_role_and_password(): void
    {
        $admin = $this->makeUser('admin');
        $role = \App\Models\Role::where('slug', 'manager')->first();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Manager',
            'email' => 'new.manager@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $role->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new.manager@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue(\Hash::check('secret123', $user->password));
    }

    public function test_admin_create_user_validation(): void
    {
        $admin = $this->makeUser('admin');
        $existing = $this->makeUser('staff');

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'A',
            'email' => $existing->email,
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'role_id' => 999999,
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role_id']);
    }

    public function test_admin_updates_user_role_and_email(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('staff');
        $role = \App\Models\Role::where('slug', 'checker')->first();

        $response = $this->actingAs($admin)->put("/admin/users/{$target->id}", [
            'name' => 'Renamed User',
            'email' => 'renamed@example.test',
            'role_id' => $role->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $fresh = $target->fresh();
        $this->assertSame('Renamed User', $fresh->name);
        $this->assertSame('renamed@example.test', $fresh->email);
        $this->assertSame($role->id, $fresh->role_id);
    }

    public function test_admin_can_toggle_status_but_not_own_status(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('staff');

        $this->actingAs($admin)->patch("/admin/users/{$target->id}/status")->assertStatus(302);
        $this->assertFalse($target->fresh()->is_active);

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/status")->assertStatus(302);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_reset_password_and_new_password_works(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('staff');

        $response = $this->actingAs($admin)->patch("/admin/users/{$target->id}/password", [
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);
        $response->assertStatus(302);

        $this->assertTrue(\Hash::check('newpassword1', $target->fresh()->password));

        // Login with the new password
        $this->post('/login', ['email' => $target->email, 'password' => 'newpassword1'])
            ->assertRedirect('/dashboard');
    }

    public function test_deactivated_user_cannot_login_and_session_blocked(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('staff', ['is_active' => false]);

        $this->post('/login', ['email' => $target->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_profile_page_shows_own_data(): void
    {
        $user = $this->makeUser('staff');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Profile');
    }
}
