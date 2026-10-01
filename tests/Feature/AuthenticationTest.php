<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat01_valid_login_redirects_to_dashboard(): void
    {
        $user = $this->makeUser('staff');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_uat02_invalid_login_shows_error(): void
    {
        $user = $this->makeUser('staff');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->makeUser('staff', ['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_validation_requires_credentials(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->actingAs($user)->get('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_protected_pages(): void
    {
        foreach (['/dashboard', '/tasks', '/notifications', '/profile'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = $this->makeUser('staff');

        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
    }

    public function test_password_is_hashed_in_database(): void
    {
        $user = $this->makeUser('staff');

        $this->assertNotSame('password', User::findOrFail($user->id)->password);
        $this->assertTrue(\Hash::check('password', $user->fresh()->password));
    }
}
